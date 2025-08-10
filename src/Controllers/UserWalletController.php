<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

class UserWalletController
{
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function list(): void
    {
        $userId = Auth::requireUser($this->request->header('X-User-Id'));
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT uw.wallet_admin_id, wa.name, wa.currency, wa.network, uw.deposit_address, uw.deposit_tag, wa.requires_tag, wa.confirmations, wa.is_enabled, uw.address_generated FROM user_wallets uw JOIN wallet_admin wa ON wa.id = uw.wallet_admin_id WHERE uw.user_id = ? ORDER BY wa.currency, wa.network');
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->response->json($rows);
    }

    public function generateAddress(): void
    {
        $userId = Auth::requireUser($this->request->header('X-User-Id'));
        $walletAdminId = (int)($this->request->params['wallet_admin_id'] ?? 0);
        if ($walletAdminId <= 0) { $this->response->json(['error' => 'Invalid wallet'], 422); return; }
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('SELECT uw.*, wa.currency, wa.network, wa.address_template, wa.requires_tag, wa.tag_label, wa.confirmations FROM user_wallets uw JOIN wallet_admin wa ON wa.id = uw.wallet_admin_id WHERE uw.user_id = ? AND uw.wallet_admin_id = ? AND uw.status = "active" AND wa.is_enabled = 1');
        $stmt->execute([$userId, $walletAdminId]);
        $uw = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$uw) { $this->response->json(['error' => 'Wallet not available'], 404); return; }

        $address = $uw['deposit_address'];
        $tag = $uw['deposit_tag'];
        $generated = (bool)$uw['address_generated'];
        $tpl = (string)$uw['address_template'];

        if (!$generated || !$address) {
            if (str_starts_with($tpl, 'xpub:')) {
                // Mock deterministic derivation in demo mode
                $hash = substr(hash('sha256', $tpl.'|'.$userId), 0, 32);
                $address = substr($uw['currency'],0,1) . $hash; // fake address-like string
                $generated = true;
            } elseif (!empty($tpl)) {
                $address = $tpl; // global address
                $generated = false;
            } else {
                $this->response->json(['error' => 'No address template configured'], 500);
                return;
            }
            $upd = $pdo->prepare('UPDATE user_wallets SET deposit_address = ?, address_generated = ? WHERE id = ?');
            $upd->execute([$address, $generated ? 1 : 0, $uw['id']]);
        }

        $this->response->json([
            'deposit_address' => $address,
            'deposit_tag' => $uw['requires_tag'] ? ($tag ?: '') : null,
            'instructions' => sprintf('Send %s (%s) to this address. Wait %d confirmations.', $uw['currency'], $uw['network'], (int)$uw['confirmations'])
        ]);
    }
}