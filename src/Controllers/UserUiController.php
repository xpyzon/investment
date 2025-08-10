<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use PDO;

class UserUiController
{
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    private function getUserOrRedirect(): array
    {
        $userId = (int)($_COOKIE['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->response->redirect('/login');
            exit;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id=? AND is_active=1');
        $stmt->execute([$userId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u) {
            setcookie('user_id', '', time() - 3600, '/');
            $this->response->redirect('/login');
            exit;
        }
        return $u;
    }

    public function loginForm(): void
    {
        $html = View::render('user/login');
        $this->response->html($html);
    }

    public function loginSubmit(): void
    {
        $f = $this->request->form();
        $email = trim($f['email'] ?? '');
        $password = (string)($f['password'] ?? '');
        if ($email === '' || $password === '') {
            $html = View::render('user/login', ['error' => 'Email and password required']);
            $this->response->html($html, 422);
            return;
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, email, password_hash, twofa_secret FROM users WHERE email=? AND is_active=1');
        $stmt->execute([$email]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u || !password_verify($password, (string)$u['password_hash'])) {
            $html = View::render('user/login', ['error' => 'Invalid credentials']);
            $this->response->html($html, 401);
            return;
        }
        $code = trim((string)($f['code'] ?? ''));
        if (!empty($u['twofa_secret'])) {
            if ($code === '' || !\App\Services\Totp::verifyCode((string)$u['twofa_secret'], $code)) {
                $html = View::render('user/login', ['error' => 'Invalid 2FA code']);
                $this->response->html($html, 401);
                return;
            }
        }
        setcookie('user_id', (string)$u['id'], time()+86400*7, '/', '', false, true);
        $this->response->redirect('/dashboard');
    }

    public function logout(): void
    {
        setcookie('user_id', '', time() - 3600, '/');
        $this->response->redirect('/login');
    }

    public function dashboard(): void
    {
        $user = $this->getUserOrRedirect();
        $html = View::render('user/dashboard', ['user' => $user]);
        $this->response->html($html);
    }

    public function products(): void
    {
        $user = $this->getUserOrRedirect();
        $pdo = Database::pdo();
        $rows = $pdo->query('SELECT id, name, type, symbol, min_invest, max_invest FROM products WHERE enabled=1 ORDER BY type, name')->fetchAll(PDO::FETCH_ASSOC);
        $html = View::render('user/products', ['user'=>$user, 'products'=>$rows]);
        $this->response->html($html);
    }

    public function investForm(): void
    {
        $user = $this->getUserOrRedirect();
        $productId = (int)($this->request->params['id'] ?? 0);
        $pdo = Database::pdo();
        $p = $pdo->prepare('SELECT * FROM products WHERE id=? AND enabled=1');
        $p->execute([$productId]);
        $product = $p->fetch(PDO::FETCH_ASSOC);
        if (!$product) { $this->response->html('Product not found', 404); return; }
        $html = View::render('user/invest_form', ['user'=>$user, 'product'=>$product]);
        $this->response->html($html);
    }

    public function investSubmit(): void
    {
        $user = $this->getUserOrRedirect();
        $f = $this->request->form();
        $productId = (int)($f['product_id'] ?? 0);
        $amount = (float)($f['amount'] ?? 0);
        if ($productId<=0 || $amount<=0) { $this->response->html('Invalid',422); return; }
        $pdo = Database::pdo();
        $p = $pdo->prepare('SELECT * FROM products WHERE id=? AND enabled=1');
        $p->execute([$productId]);
        $product = $p->fetch(PDO::FETCH_ASSOC);
        if (!$product) { $this->response->html('Product not found',404); return; }
        if ($product['min_invest'] > 0 && $amount < (float)$product['min_invest']) { $this->response->html('Below minimum',422); return; }
        if ($product['max_invest'] > 0 && $amount > (float)$product['max_invest']) { $this->response->html('Above maximum',422); return; }
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO investments (user_id, product_id, type, units, entry_price, status) VALUES (?,?,?,?,?,?)');
            $ins->execute([(int)$user['id'], $productId, $product['type'], $amount, 1.0, 'active']);
            $txn = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, currency, meta) VALUES (?,?,?,?,?)');
            $txn->execute([(int)$user['id'], 'buy', $amount, 'USD', json_encode(['product_id'=>$productId])]);
            $pdo->commit();
            $this->response->redirect('/products');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            $this->response->html('Failed to invest',500);
        }
    }

    public function wallets(): void
    {
        $user = $this->getUserOrRedirect();
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT uw.wallet_admin_id, wa.name, wa.currency, wa.network, uw.deposit_address, uw.deposit_tag, wa.requires_tag, wa.confirmations, wa.is_enabled, uw.address_generated FROM user_wallets uw JOIN wallet_admin wa ON wa.id = uw.wallet_admin_id WHERE uw.user_id = ? ORDER BY wa.currency, wa.network');
        $stmt->execute([(int)$user['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $html = View::render('user/wallets', ['user'=>$user, 'wallets'=>$rows]);
        $this->response->html($html);
    }

    public function walletsGenerate(): void
    {
        $user = $this->getUserOrRedirect();
        $walletAdminId = (int)($this->request->params['id'] ?? 0);
        if ($walletAdminId<=0) { $this->response->redirect('/wallets'); return; }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT uw.*, wa.currency, wa.network, wa.address_template, wa.requires_tag, wa.tag_label, wa.confirmations FROM user_wallets uw JOIN wallet_admin wa ON wa.id = uw.wallet_admin_id WHERE uw.user_id = ? AND uw.wallet_admin_id = ? AND uw.status = "active" AND wa.is_enabled = 1');
        $stmt->execute([(int)$user['id'], $walletAdminId]);
        $uw = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($uw) {
            $address = $uw['deposit_address'];
            $generated = (bool)$uw['address_generated'];
            $tpl = (string)$uw['address_template'];
            if (!$generated || !$address) {
                if (str_starts_with($tpl, 'xpub:')) {
                    $hash = substr(hash('sha256', $tpl.'|'.$user['id']), 0, 32);
                    $address = substr($uw['currency'],0,1) . $hash;
                    $generated = true;
                } elseif (!empty($tpl)) {
                    $address = $tpl;
                    $generated = false;
                }
                $upd = $pdo->prepare('UPDATE user_wallets SET deposit_address = ?, address_generated = ? WHERE id = ?');
                $upd->execute([$address, $generated ? 1 : 0, $uw['id']]);
            }
        }
        $this->response->redirect('/wallets');
    }

    public function withdrawals(): void
    {
        $user = $this->getUserOrRedirect();
        $html = View::render('user/withdrawals', ['user'=>$user]);
        $this->response->html($html);
    }

    public function withdrawalsSubmit(): void
    {
        $user = $this->getUserOrRedirect();
        $f = $this->request->form();
        $amount = (float)($f['amount'] ?? 0);
        $currency = trim($f['currency'] ?? '');
        $address = trim($f['address'] ?? '');
        if ($amount<=0 || $currency==='' || $address==='') { $this->response->html('Invalid',422); return; }
        $pdo = Database::pdo();
        $ins = $pdo->prepare('INSERT INTO withdrawals (user_id, amount, currency, address, status) VALUES (?,?,?,?,'".'pending'".')');
        $ins->execute([(int)$user['id'], $amount, $currency, $address]);
        $this->response->redirect('/withdrawals');
    }

    public function account(): void
    {
        $user = $this->getUserOrRedirect();
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT twofa_secret FROM users WHERE id=?');
        $stmt->execute([(int)$user['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $html = View::render('user/account', ['user'=>$user, 'twofa_enabled'=>!empty($row['twofa_secret'])]);
        $this->response->html($html);
    }

    public function accountPassword(): void
    {
        $user = $this->getUserOrRedirect();
        $f = $this->request->form();
        $current = (string)($f['current'] ?? '');
        $new = (string)($f['new'] ?? '');
        if ($current==='' || $new==='') { $this->response->html('Invalid',422); return; }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id=?');
        $stmt->execute([(int)$user['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($current, (string)$row['password_hash'])) { $this->response->html('Wrong password',403); return; }
        $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_BCRYPT), (int)$user['id']]);
        $this->response->redirect('/account');
    }

    public function account2faSetup(): void
    {
        $user = $this->getUserOrRedirect();
        $secret = \App\Services\Totp::generateSecret();
        $uri = \App\Services\Totp::provisioningUri((string)$user['email'], 'Investment', $secret);
        $html = View::render('user/account_2fa_setup', ['user'=>$user, 'secret'=>$secret, 'uri'=>$uri]);
        $this->response->html($html);
    }

    public function account2faEnable(): void
    {
        $user = $this->getUserOrRedirect();
        $f = $this->request->form();
        $secret = (string)($f['secret'] ?? '');
        $code = (string)($f['code'] ?? '');
        if (!\App\Services\Totp::verifyCode($secret, $code)) {
            $uri = \App\Services\Totp::provisioningUri((string)$user['email'], 'Investment', $secret);
            $html = View::render('user/account_2fa_setup', ['user'=>$user, 'secret'=>$secret, 'uri'=>$uri, 'error'=>'Invalid code']);
            $this->response->html($html, 422);
            return;
        }
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE users SET twofa_secret=? WHERE id=?')->execute([$secret, (int)$user['id']]);
        $this->response->redirect('/account');
    }

    public function account2faDisable(): void
    {
        $user = $this->getUserOrRedirect();
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE users SET twofa_secret=NULL WHERE id=?')->execute([(int)$user['id']]);
        $this->response->redirect('/account');
    }

    public function portfolio(): void
    {
        $user = $this->getUserOrRedirect();
        $pdo = Database::pdo();
        $inv = $pdo->prepare('SELECT i.*, p.name FROM investments i JOIN products p ON p.id=i.product_id WHERE i.user_id=? ORDER BY i.created_at DESC');
        $inv->execute([(int)$user['id']]);
        $investments = $inv->fetchAll(PDO::FETCH_ASSOC);
        $tx = $pdo->prepare('SELECT * FROM transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 50');
        $tx->execute([(int)$user['id']]);
        $transactions = $tx->fetchAll(PDO::FETCH_ASSOC);
        $html = View::render('user/portfolio', ['user'=>$user, 'investments'=>$investments, 'transactions'=>$transactions]);
        $this->response->html($html);
    }

    public function market(): void
    {
        $user = $this->getUserOrRedirect();
        $html = View::render('user/market', ['user'=>$user]);
        $this->response->html($html);
    }
}