<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

class AdminWalletController
{
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function create(): void
    {
        Auth::requireAdmin($this->request->header('X-Admin-Key'));
        $data = $this->request->input();
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('INSERT INTO wallet_admin (name, currency, network, address_template, requires_tag, tag_label, confirmations, icon_url, is_enabled, created_by, updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['name'] ?? '',
            $data['currency'] ?? '',
            $data['network'] ?? '',
            $data['address_template'] ?? null,
            !empty($data['requires_tag']) ? 1 : 0,
            $data['tag_label'] ?? null,
            isset($data['confirmations']) ? (int)$data['confirmations'] : 3,
            $data['icon_url'] ?? null,
            isset($data['is_enabled']) ? (int)(bool)$data['is_enabled'] : 1,
            0,
            0,
        ]);
        $id = (int)$pdo->lastInsertId();

        // Assign to all users
        $assignStmt = $pdo->prepare('INSERT IGNORE INTO user_wallets (user_id, wallet_admin_id, status) SELECT id, ?, "active" FROM users');
        $assignStmt->execute([$id]);

        $this->response->json(['id' => $id, 'assigned_users' => $assignStmt->rowCount()]);
    }

    public function update(): void
    {
        Auth::requireAdmin($this->request->header('X-Admin-Key'));
        $data = $this->request->input();
        $id = (int)($this->request->params['id'] ?? 0);
        if ($id <= 0) {
            $this->response->json(['error' => 'Invalid id'], 422);
            return;
        }

        $pdo = Database::pdo();
        // Fetch existing
        $cur = $pdo->prepare('SELECT * FROM wallet_admin WHERE id = ?');
        $cur->execute([$id]);
        $existing = $cur->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            $this->response->json(['error' => 'Not found'], 404);
            return;
        }

        $fields = ['name','currency','network','address_template','requires_tag','tag_label','confirmations','icon_url','is_enabled'];
        $sets = [];
        $values = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $sets[] = "$f = ?";
                if ($f === 'requires_tag' || $f === 'is_enabled') {
                    $values[] = (int)(bool)$data[$f];
                } elseif ($f === 'confirmations') {
                    $values[] = (int)$data[$f];
                } else {
                    $values[] = $data[$f];
                }
            }
        }
        if (!$sets) {
            $this->response->json(['updated' => 0]);
            return;
        }
        $values[] = $id;
        $sql = 'UPDATE wallet_admin SET ' . implode(',', $sets) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        // Propagate changes: if address_template changed and not xpub, update all user_wallets deposit_address to new global address
        $templateChanged = array_key_exists('address_template', $data) && (string)$data['address_template'] !== (string)($existing['address_template'] ?? '');
        if ($templateChanged) {
            $newTpl = (string)$data['address_template'];
            if (!str_starts_with($newTpl, 'xpub:') && $newTpl !== '') {
                $upd = $pdo->prepare('UPDATE user_wallets SET deposit_address = ?, address_generated = 0 WHERE wallet_admin_id = ?');
                $upd->execute([$newTpl, $id]);
            } else {
                // mark as not generated so next user request derives a new one
                $upd = $pdo->prepare('UPDATE user_wallets SET address_generated = 0 WHERE wallet_admin_id = ?');
                $upd->execute([$id]);
            }
        }

        $this->response->json(['updated' => $stmt->rowCount()]);
    }

    public function toggle(): void
    {
        Auth::requireAdmin($this->request->header('X-Admin-Key'));
        $id = (int)($this->request->params['id'] ?? 0);
        $data = $this->request->input();
        $enabled = isset($data['is_enabled']) ? (int)(bool)$data['is_enabled'] : null;
        if ($id <= 0 || $enabled === null) {
            $this->response->json(['error' => 'Invalid request'], 422);
            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE wallet_admin SET is_enabled = ? WHERE id = ?');
        $stmt->execute([$enabled, $id]);
        $stmt2 = $pdo->prepare('UPDATE user_wallets SET status = ? WHERE wallet_admin_id = ?');
        $stmt2->execute([$enabled ? 'active' : 'disabled', $id]);
        $this->response->json(['updated' => $stmt->rowCount()]);
    }

    public function assignAll(): void
    {
        Auth::requireAdmin($this->request->header('X-Admin-Key'));
        $id = (int)($this->request->params['id'] ?? 0);
        if ($id <= 0) { $this->response->json(['error' => 'Invalid id'], 422); return; }
        $pdo = Database::pdo();
        $assignStmt = $pdo->prepare('INSERT IGNORE INTO user_wallets (user_id, wallet_admin_id, status) SELECT id, ?, "active" FROM users');
        $assignStmt->execute([$id]);
        $this->response->json(['assigned' => $assignStmt->rowCount()]);
    }

    public function creditManual(): void
    {
        Auth::requireAdmin($this->request->header('X-Admin-Key'));
        $data = $this->request->input();
        $id = (int)($this->request->params['id'] ?? 0);
        $userId = (int)($data['user_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);
        $currency = (string)($data['currency'] ?? '');
        $txid = (string)($data['txid'] ?? '');
        if ($id<=0 || $userId<=0 || $amount<=0 || $currency==='') {
            $this->response->json(['error' => 'Invalid request'], 422);
            return;
        }
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $dep = $pdo->prepare('INSERT INTO deposits (user_id, amount, currency, txid, address, network, status, fees) VALUES (?,?,?,?,?,?,?,?)');
            $dep->execute([$userId, $amount, $currency, $txid ?: ('manual-'.time()), null, null, 'confirmed', 0]);
            $txn = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, currency, meta) VALUES (?,?,?,?,?)');
            $txn->execute([$userId, 'deposit', $amount, $currency, json_encode(['txid' => $txid])]);
            $pdo->commit();
            $this->response->json(['ok' => true]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->response->json(['error' => 'Failed to credit', 'message' => $e->getMessage()], 500);
        }
    }
}