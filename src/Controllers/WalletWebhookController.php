<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Services\Mailer;
use App\Services\EmailTemplates;
use PDO;

class WalletWebhookController
{
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function handle(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        $sig = $this->request->header('X-Webhook-Signature') ?? '';
        $secret = Env::get('WEBHOOK_SECRET', '');
        $calc = hash_hmac('sha256', $raw, $secret);
        if (!$secret || !hash_equals($calc, $sig)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid signature']);
            return;
        }

        $data = json_decode($raw, true) ?: [];
        $currency = (string)($data['currency'] ?? '');
        $network = (string)($data['network'] ?? '');
        $txid = (string)($data['txid'] ?? '');
        $to = (string)($data['to'] ?? '');
        $amount = (float)($data['amount'] ?? 0);
        $confirmations = (int)($data['confirmations'] ?? 0);
        $memo = $data['memo'] ?? null;

        if ($currency === '' || $network === '' || $txid === '' || $to === '' || $amount <= 0) {
            $this->response->json(['error' => 'Invalid payload'], 422);
            return;
        }

        $pdo = Database::pdo();
        // Find matching user wallet
        $find = $pdo->prepare('SELECT uw.*, wa.confirmations as required_confirmations, u.email, u.name FROM user_wallets uw JOIN wallet_admin wa ON wa.id = uw.wallet_admin_id JOIN users u ON u.id=uw.user_id WHERE (uw.deposit_address = ? OR uw.deposit_tag = ?) LIMIT 1');
        $find->execute([$to, $memo]);
        $uw = $find->fetch(PDO::FETCH_ASSOC);

        $status = 'pending';
        $userId = null;
        $required = 3;
        $userEmail = null;
        if ($uw) {
            $userId = (int)$uw['user_id'];
            $userEmail = $uw['email'] ?? null;
            $required = (int)($uw['required_confirmations'] ?? 3);
            if ($confirmations >= $required) { $status = 'confirmed'; }
        } else {
            // Fallback: match by global admin wallets
            $adm = $pdo->prepare('SELECT id, confirmations FROM wallet_admin WHERE address_template = ? LIMIT 1');
            $adm->execute([$to]);
            $wa = $adm->fetch(PDO::FETCH_ASSOC);
            if ($wa) {
                $required = (int)$wa['confirmations'];
                if ($confirmations >= $required) { $status = 'confirmed'; }
            }
        }

        // Upsert deposit
        $dep = $pdo->prepare('SELECT id, status, user_id FROM deposits WHERE txid = ? LIMIT 1');
        $dep->execute([$txid]);
        $existing = $dep->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $upd = $pdo->prepare('UPDATE deposits SET status = ?, fees = COALESCE(fees,0), updated_at = NOW() WHERE id = ?');
            $upd->execute([$status, $existing['id']]);
        } else {
            $ins = $pdo->prepare('INSERT INTO deposits (user_id, amount, currency, txid, address, network, status, fees) VALUES (?,?,?,?,?,?,?,0)');
            $ins->execute([$userId, $amount, $currency, $txid, $to, $network, $status]);
        }

        // Notifications (HTML)
        if ($userEmail) {
            $mailer = new Mailer();
            if ($status === 'confirmed') {
                $mailer->send($userEmail, 'Deposit confirmed', EmailTemplates::depositConfirmed($currency, (string)$amount, $txid));
            } else {
                $mailer->send($userEmail, 'Deposit received (pending)', EmailTemplates::depositPending($currency, (string)$amount, $required, $txid));
            }
        }

        // Credit ledger on confirm
        if ($status === 'confirmed' && $userId) {
            $txn = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, currency, meta) VALUES (?,?,?,?,?)');
            $txn->execute([$userId, 'deposit', $amount, $currency, json_encode(['txid' => $txid])]);
        }

        $this->response->json(['ok' => true, 'status' => $status]);
    }
}