<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\Mailer;
use PDO;

class WithdrawalController
{
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function request(): void
    {
        $userId = Auth::requireUser($this->request->header('X-User-Id'));
        $data = $this->request->input();
        $amount = (float)($data['amount'] ?? 0);
        $currency = (string)($data['currency'] ?? '');
        $address = (string)($data['address'] ?? '');
        if ($amount<=0 || $currency==='' || $address==='') { $this->response->json(['error'=>'Invalid request'],422); return; }

        $pdo = Database::pdo();
        $ins = $pdo->prepare('INSERT INTO withdrawals (user_id, amount, currency, address, status) VALUES (?,?,?,?,"pending")');
        $ins->execute([$userId, $amount, $currency, $address]);

        // Notify user (HTML)
        $this->sendEmail($userId, 'Withdrawal requested', $this->tplWithdrawalRequested($amount, $currency, $address));
        $this->response->json(['ok'=>true]);
    }

    public function adminList(): void
    {
        Auth::requireAdmin($this->request->header('X-Admin-Key'));
        $pdo = Database::pdo();
        $rows = $pdo->query('SELECT w.*, u.email, u.name FROM withdrawals w JOIN users u ON u.id=w.user_id ORDER BY w.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
        $this->response->json($rows);
    }

    public function adminApprove(): void
    {
        Auth::requireAdmin($this->request->header('X-Admin-Key'));
        $id = (int)($this->request->params['id'] ?? 0);
        $pdo = Database::pdo();
        $w = $pdo->prepare('SELECT * FROM withdrawals WHERE id=?');
        $w->execute([$id]);
        $wd = $w->fetch(PDO::FETCH_ASSOC);
        if (!$wd || $wd['status'] !== 'pending') { $this->response->json(['error'=>'Invalid state'],422); return; }
        $pdo->prepare('UPDATE withdrawals SET status="approved" WHERE id=?')->execute([$id]);
        $this->sendEmail((int)$wd['user_id'], 'Withdrawal approved', $this->tplWithdrawalApproved((float)$wd['amount'], $wd['currency']));
        $this->response->json(['ok'=>true]);
    }

    public function adminReject(): void
    {
        Auth::requireAdmin($this->request->header('X-Admin-Key'));
        $id = (int)($this->request->params['id'] ?? 0);
        $pdo = Database::pdo();
        $w = $pdo->prepare('SELECT * FROM withdrawals WHERE id=?');
        $w->execute([$id]);
        $wd = $w->fetch(PDO::FETCH_ASSOC);
        if (!$wd || $wd['status'] !== 'pending') { $this->response->json(['error'=>'Invalid state'],422); return; }
        $pdo->prepare('UPDATE withdrawals SET status="rejected" WHERE id=?')->execute([$id]);
        $this->sendEmail((int)$wd['user_id'], 'Withdrawal rejected', $this->tplWithdrawalRejected((float)$wd['amount'], $wd['currency']));
        $this->response->json(['ok'=>true]);
    }

    private function sendEmail(int $userId, string $subject, string $html): void
    {
        $pdo = Database::pdo();
        $u = $pdo->prepare('SELECT email FROM users WHERE id=?');
        $u->execute([$userId]);
        $user = $u->fetch(PDO::FETCH_ASSOC);
        if ($user && !empty($user['email'])) {
            try {
                (new Mailer())->send($user['email'], $subject, $html);
            } catch (\Throwable $e) {
                // ignore email errors in API response
            }
        }
    }

    private function tplBase(string $title, string $body): string
    {
        return '<!doctype html><html><head><meta charset="utf-8"><title>'.htmlspecialchars($title).'</title></head><body style="font-family:Inter,system-ui,Arial;color:#000;background:#fff;">'
            .'<div style="max-width:600px;margin:0 auto;padding:24px;border:1px solid #000">'
            .'<h1 style="font-size:20px;margin:0 0 16px 0">'.htmlspecialchars($title).'</h1>'
            .$body
            .'<hr style="border:0;border-top:1px solid #000;margin:24px 0"/>'
            .'<p style="font-size:12px;color:#000">This is an automated message. Do not reply.</p>'
            .'</div></body></html>';
    }

    private function tplWithdrawalRequested(float $amount, string $currency, string $address): string
    {
        $body = '<p>Your withdrawal request has been received and is pending manual approval.</p>'
               .'<p><strong>Amount:</strong> '.number_format($amount, 2).' '.htmlspecialchars($currency).'</p>'
               .'<p><strong>Address:</strong> '.htmlspecialchars($address).'</p>';
        return $this->tplBase('Withdrawal requested', $body);
    }

    private function tplWithdrawalApproved(float $amount, string $currency): string
    {
        $body = '<p>Your withdrawal has been approved and will be processed shortly.</p>'
               .'<p><strong>Amount:</strong> '.number_format($amount, 2).' '.htmlspecialchars($currency).'</p>';
        return $this->tplBase('Withdrawal approved', $body);
    }

    private function tplWithdrawalRejected(float $amount, string $currency): string
    {
        $body = '<p>Your withdrawal request was rejected. Please contact support for details.</p>'
               .'<p><strong>Amount:</strong> '.number_format($amount, 2).' '.htmlspecialchars($currency).'</p>';
        return $this->tplBase('Withdrawal rejected', $body);
    }
}