<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use PDO;

class AdminUiController
{
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    private function ensureAdmin(): void
    {
        Auth::requireAdmin($this->request->header('X-Admin-Key'));
    }

    public function walletsIndex(): void
    {
        $this->ensureAdmin();
        $pdo = Database::pdo();
        $rows = $pdo->query('SELECT * FROM wallet_admin ORDER BY currency, network')->fetchAll(PDO::FETCH_ASSOC);
        $html = View::render('admin/wallets/index', ['wallets' => $rows]);
        $this->response->html($html);
    }

    public function walletsCreateForm(): void
    {
        $this->ensureAdmin();
        $html = View::render('admin/wallets/create');
        $this->response->html($html);
    }

    public function walletsCreate(): void
    {
        $this->ensureAdmin();
        $f = $this->request->form();
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO wallet_admin (name, currency, network, address_template, requires_tag, tag_label, confirmations, icon_url, is_enabled) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            trim($f['name'] ?? ''),
            strtoupper(trim($f['currency'] ?? '')),
            strtolower(trim($f['network'] ?? '')),
            $f['address_template'] ?? null,
            !empty($f['requires_tag']) ? 1 : 0,
            $f['tag_label'] ?? null,
            (int)($f['confirmations'] ?? 3),
            $f['icon_url'] ?? null,
            !empty($f['is_enabled']) ? 1 : 0,
        ]);
        $id = (int)$pdo->lastInsertId();
        $assign = $pdo->prepare('INSERT IGNORE INTO user_wallets (user_id, wallet_admin_id, status) SELECT id, ?, "active" FROM users');
        $assign->execute([$id]);
        $this->response->redirect('/admin/ui/wallets');
    }

    public function walletsEditForm(): void
    {
        $this->ensureAdmin();
        $id = (int)($this->request->params['id'] ?? 0);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM wallet_admin WHERE id = ?');
        $stmt->execute([$id]);
        $wa = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$wa) { $this->response->html('Not found', 404); return; }
        $html = View::render('admin/wallets/edit', ['wa' => $wa]);
        $this->response->html($html);
    }

    public function walletsUpdate(): void
    {
        $this->ensureAdmin();
        $id = (int)($this->request->params['id'] ?? 0);
        $f = $this->request->form();
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE wallet_admin SET name=?, currency=?, network=?, address_template=?, requires_tag=?, tag_label=?, confirmations=?, icon_url=?, is_enabled=? WHERE id=?');
        $stmt->execute([
            trim($f['name'] ?? ''),
            strtoupper(trim($f['currency'] ?? '')),
            strtolower(trim($f['network'] ?? '')),
            $f['address_template'] ?? null,
            !empty($f['requires_tag']) ? 1 : 0,
            $f['tag_label'] ?? null,
            (int)($f['confirmations'] ?? 3),
            $f['icon_url'] ?? null,
            !empty($f['is_enabled']) ? 1 : 0,
            $id
        ]);

        // propagate template change: set address_generated=0
        $pdo->prepare('UPDATE user_wallets SET address_generated=0 WHERE wallet_admin_id=?')->execute([$id]);
        $this->response->redirect('/admin/ui/wallets');
    }

    public function walletsToggle(): void
    {
        $this->ensureAdmin();
        $id = (int)($this->request->params['id'] ?? 0);
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE wallet_admin SET is_enabled = 1 - is_enabled WHERE id=?')->execute([$id]);
        $pdo->prepare('UPDATE user_wallets SET status = CASE status WHEN "active" THEN "disabled" ELSE "active" END WHERE wallet_admin_id=?')->execute([$id]);
        $this->response->redirect('/admin/ui/wallets');
    }

    public function walletsAssignAll(): void
    {
        $this->ensureAdmin();
        $id = (int)($this->request->params['id'] ?? 0);
        $pdo = Database::pdo();
        $assign = $pdo->prepare('INSERT IGNORE INTO user_wallets (user_id, wallet_admin_id, status) SELECT id, ?, "active" FROM users');
        $assign->execute([$id]);
        $this->response->redirect('/admin/ui/wallets');
    }
}