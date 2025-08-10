<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/NowPayments.php';

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?? '/';

// Basic routing
function route(string $method, string $pattern, callable $handler): void {
    static $routes = [];
    if ($handler) {
        $routes[] = [$method, $pattern, $handler];
        $GLOBALS['__routes'] = $routes;
        return;
    }
}

function dispatch(PDO $pdo, string $method, string $path): void {
    $routes = $GLOBALS['__routes'] ?? [];
    foreach ($routes as [$m, $pattern, $handler]) {
        if ($m !== $method) continue;
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        if (preg_match($regex, $path, $mch)) {
            $params = array_filter($mch, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler($pdo, $params);
            return;
        }
    }
    json_response(['message' => 'Not Found'], 404);
}

// Handlers
function handle_install_get(PDO $pdo): void {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><title>Installer</title></head><body>';
    echo '<h1>Native PHP Installer</h1>';
    echo '<p>This will initialize the SQLite database and create an admin user.</p>';
    echo '<form method="post" action="/install">';
    echo 'Admin Name: <input name="name" value="Admin"/><br/>';
    echo 'Admin Email: <input name="email" value="admin@example.com"/><br/>';
    echo 'Admin Password: <input type="password" name="password" value="admin123"/><br/>';
    echo '<button type="submit">Install</button>';
    echo '</form>';
    echo '</body></html>';
}

function handle_install_post(PDO $pdo): void {
    // Run schema
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    if ($schema === false) {
        json_response(['message' => 'Schema not found'], 500);
        return;
    }
    $pdo->beginTransaction();
    try {
        $pdo->exec($schema);
        // Seed admin if not exists
        $name = $_POST['name'] ?? 'Admin';
        $email = $_POST['email'] ?? 'admin@example.com';
        $password = $_POST['password'] ?? 'admin123';

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $exists = $stmt->fetchColumn();
        if (!$exists) {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, is_active, created_at, updated_at) VALUES (:name, :email, :password, :role, 1, :now, :now)');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => password_hash($password, PASSWORD_BCRYPT),
                ':role' => 'admin',
                ':now' => now_iso8601(),
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['message' => 'Install failed'], 500);
        return;
    }

    json_response(['ok' => true, 'message' => 'Installed', 'admin' => ['email' => $email]], 201);
}

function handle_login(PDO $pdo): void {
    json_try(function () use ($pdo) {
        $data = json_input();
        $data = validate($data, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $data['email']]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($data['password'], $user['password'])) {
            json_response(['message' => 'Invalid credentials'], 422);
            return;
        }
        if (!(int)$user['is_active']) {
            json_response(['message' => 'Account disabled'], 403);
            return;
        }
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare('INSERT INTO api_tokens (user_id, token, abilities, created_at) VALUES (:uid, :tok, :ab, :now)');
        $stmt->execute([':uid' => $user['id'], ':tok' => $token, ':ab' => '*', ':now' => now_iso8601()]);
        json_response(['token' => $token, 'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'investor',
        ]]);
    });
}

function handle_logout(PDO $pdo): void {
    $token = bearer_token();
    if ($token) {
        $stmt = $pdo->prepare('DELETE FROM api_tokens WHERE token = :t');
        $stmt->execute([':t' => $token]);
    }
    json_response(['ok' => true]);
}

function handle_me(PDO $pdo): void {
    $user = require_auth($pdo);
    json_response(['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role'] ?? 'investor']);
}

function admin_wallet_index(PDO $pdo): void {
    require_admin($pdo);
    $rows = $pdo->query('SELECT * FROM wallet_admin ORDER BY id DESC')->fetchAll();
    json_response($rows);
}

function admin_wallet_store(PDO $pdo): void {
    $admin = require_admin($pdo);
    json_try(function () use ($pdo, $admin) {
        $data = validate(json_input(), [
            'name' => 'required|string|max:191',
            'currency' => 'required|string|max:10',
            'network' => 'required|string|max:50',
            'address_template' => 'string',
            'requires_tag' => 'boolean',
            'tag_label' => 'string|max:50',
            'confirmations' => 'required|integer|min:0|max:100',
            'icon_url' => 'url|max:255',
            'is_enabled' => 'boolean',
            'use_nowpayments' => 'boolean',
        ]);
        $defaults = [
            'address_template' => null,
            'requires_tag' => 0,
            'tag_label' => null,
            'icon_url' => null,
            'is_enabled' => 1,
            'use_nowpayments' => 0,
        ];
        $data = array_merge($defaults, $data);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO wallet_admin (name, currency, network, address_template, requires_tag, tag_label, confirmations, icon_url, is_enabled, use_nowpayments, created_by, updated_by, created_at, updated_at) VALUES (:name, :currency, :network, :address_template, :requires_tag, :tag_label, :confirmations, :icon_url, :is_enabled, :use_nowpayments, :created_by, :updated_by, :now, :now)');
            $stmt->execute([
                ':name' => $data['name'],
                ':currency' => $data['currency'],
                ':network' => $data['network'],
                ':address_template' => $data['address_template'],
                ':requires_tag' => (int)$data['requires_tag'],
                ':tag_label' => $data['tag_label'],
                ':confirmations' => (int)$data['confirmations'],
                ':icon_url' => $data['icon_url'],
                ':is_enabled' => (int)$data['is_enabled'],
                ':use_nowpayments' => (int)$data['use_nowpayments'],
                ':created_by' => (int)$admin['id'],
                ':updated_by' => (int)$admin['id'],
                ':now' => now_iso8601(),
            ]);
            $walletId = (int)$pdo->lastInsertId();

            // Assign to all users
            $count = 0;
            $users = $pdo->query('SELECT id FROM users')->fetchAll();
            foreach ($users as $u) {
                $stmt = $pdo->prepare('INSERT OR IGNORE INTO user_wallets (user_id, wallet_admin_id, deposit_address, deposit_tag, address_generated, status, created_at, updated_at) VALUES (:uid, :wid, NULL, NULL, 0, "active", :now, :now)');
                $stmt->execute([':uid' => (int)$u['id'], ':wid' => $walletId, ':now' => now_iso8601()]);
                $count += $stmt->rowCount() > 0 ? 1 : 0;
            }

            // Record change
            $stmt = $pdo->prepare('INSERT INTO wallet_admin_changes (wallet_admin_id, admin_id, change_type, change_payload, created_at) VALUES (:wid, :aid, :type, :payload, :now)');
            $stmt->execute([
                ':wid' => $walletId,
                ':aid' => (int)$admin['id'],
                ':type' => 'create',
                ':payload' => json_encode($data),
                ':now' => now_iso8601(),
            ]);

            $pdo->commit();
            $wallet = $pdo->query('SELECT * FROM wallet_admin WHERE id = ' . $walletId)->fetch();
            json_response($wallet, 201);
        } catch (Throwable $e) {
            $pdo->rollBack();
            json_response(['message' => 'Failed to create wallet'], 500);
        }
    });
}

function admin_wallet_update(PDO $pdo, array $params): void {
    $admin = require_admin($pdo);
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) { json_response(['message' => 'Invalid ID'], 404); return; }

    json_try(function () use ($pdo, $admin, $id) {
        $data = json_input();
        // Whitelist
        $fields = ['name','currency','network','address_template','requires_tag','tag_label','confirmations','icon_url','is_enabled','use_nowpayments'];
        $sets = [];
        $bind = [':id' => $id, ':now' => now_iso8601(), ':updated_by' => (int)$admin['id']];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $sets[] = "$f = :$f";
                $bind[":$f"] = in_array($f, ['requires_tag','is_enabled','use_nowpayments'], true) ? (int)$data[$f] : $data[$f];
            }
        }
        if (!$sets) { json_response($pdo->query('SELECT * FROM wallet_admin WHERE id = ' . $id)->fetch()); return; }

        $pdo->beginTransaction();
        try {
            $sql = 'UPDATE wallet_admin SET ' . implode(', ', $sets) . ', updated_by = :updated_by, updated_at = :now WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind);

            // Propagate
            if (array_key_exists('is_enabled', $data)) {
                $stmt = $pdo->prepare('UPDATE user_wallets SET status = CASE WHEN :en = 1 THEN "active" ELSE "disabled" END, updated_at = :now WHERE wallet_admin_id = :wid');
                $stmt->execute([':en' => (int)$data['is_enabled'], ':now' => now_iso8601(), ':wid' => $id]);
            }
            if (array_key_exists('address_template', $data)) {
                $stmt = $pdo->prepare('UPDATE user_wallets SET deposit_address = :tpl, updated_at = :now WHERE wallet_admin_id = :wid AND address_generated = 0');
                $stmt->execute([':tpl' => (string)$data['address_template'], ':now' => now_iso8601(), ':wid' => $id]);
            }

            // Audit
            $stmt = $pdo->prepare('INSERT INTO wallet_admin_changes (wallet_admin_id, admin_id, change_type, change_payload, created_at) VALUES (:wid, :aid, :type, :payload, :now)');
            $stmt->execute([
                ':wid' => $id,
                ':aid' => (int)$admin['id'],
                ':type' => 'update',
                ':payload' => json_encode($data),
                ':now' => now_iso8601(),
            ]);

            $pdo->commit();
            $wallet = $pdo->query('SELECT * FROM wallet_admin WHERE id = ' . $id)->fetch();
            json_response($wallet);
        } catch (Throwable $e) {
            $pdo->rollBack();
            json_response(['message' => 'Failed to update wallet'], 500);
        }
    });
}

function admin_wallet_toggle(PDO $pdo, array $params): void {
    $admin = require_admin($pdo);
    $id = (int)($params['id'] ?? 0);
    json_try(function () use ($pdo, $admin, $id) {
        $data = validate(json_input(), ['is_enabled' => 'required|boolean']);
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE wallet_admin SET is_enabled = :en, updated_by = :uid, updated_at = :now WHERE id = :id');
            $stmt->execute([':en' => (int)$data['is_enabled'], ':uid' => (int)$admin['id'], ':now' => now_iso8601(), ':id' => $id]);
            $stmt = $pdo->prepare('UPDATE user_wallets SET status = CASE WHEN :en = 1 THEN "active" ELSE "disabled" END, updated_at = :now WHERE wallet_admin_id = :wid');
            $stmt->execute([':en' => (int)$data['is_enabled'], ':now' => now_iso8601(), ':wid' => $id]);
            $stmt = $pdo->prepare('INSERT INTO wallet_admin_changes (wallet_admin_id, admin_id, change_type, change_payload, created_at) VALUES (:wid, :aid, :type, :payload, :now)');
            $stmt->execute([':wid' => $id, ':aid' => (int)$admin['id'], ':type' => 'toggle', ':payload' => json_encode($data), ':now' => now_iso8601()]);
            $pdo->commit();
            json_response(['ok' => true]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            json_response(['message' => 'Failed to toggle wallet'], 500);
        }
    });
}

function admin_wallet_assign(PDO $pdo, array $params): void {
    $admin = require_admin($pdo);
    $id = (int)($params['id'] ?? 0);
    $count = 0;
    $pdo->beginTransaction();
    try {
        $users = $pdo->query('SELECT id FROM users')->fetchAll();
        foreach ($users as $u) {
            $stmt = $pdo->prepare('INSERT OR IGNORE INTO user_wallets (user_id, wallet_admin_id, deposit_address, deposit_tag, address_generated, status, created_at, updated_at) VALUES (:uid, :wid, NULL, NULL, 0, "active", :now, :now)');
            $stmt->execute([':uid' => (int)$u['id'], ':wid' => $id, ':now' => now_iso8601()]);
            $count += $stmt->rowCount() > 0 ? 1 : 0;
        }
        $stmt = $pdo->prepare('INSERT INTO wallet_admin_changes (wallet_admin_id, admin_id, change_type, change_payload, created_at) VALUES (:wid, :aid, :type, :payload, :now)');
        $stmt->execute([':wid' => $id, ':aid' => (int)$admin['id'], ':type' => 'assign', ':payload' => json_encode(['assigned' => $count]), ':now' => now_iso8601()]);
        $pdo->commit();
        json_response(['assigned' => $count]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['message' => 'Failed to assign'], 500);
    }
}

function admin_wallet_credit_manual(PDO $pdo, array $params): void {
    $admin = require_admin($pdo);
    $id = (int)($params['id'] ?? 0);
    json_try(function () use ($pdo, $admin, $id) {
        $data = validate(json_input(), [
            'user_id' => 'required|integer',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'txid' => 'required|string|max:191',
            'notes' => 'string',
        ]);
        // Ensure wallet exists
        $stmt = $pdo->prepare('SELECT * FROM wallet_admin WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $wallet = $stmt->fetch();
        if (!$wallet) { json_response(['message' => 'Wallet not found'], 404); return; }

        // Ensure txid unique
        $stmt = $pdo->prepare('SELECT id FROM deposits WHERE txid = :tx');
        $stmt->execute([':tx' => $data['txid']]);
        if ($stmt->fetchColumn()) { json_response(['message' => 'txid must be unique'], 422); return; }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO deposits (user_id, amount, currency, txid, address, network, status, fees, confirmations, wallet_admin_id, created_at, updated_at) VALUES (:uid, :amt, :cur, :tx, :addr, :net, :status, 0, :conf, :wid, :now, :now)');
            $stmt->execute([
                ':uid' => (int)$data['user_id'],
                ':amt' => (string)$data['amount'],
                ':cur' => $data['currency'],
                ':tx' => $data['txid'],
                ':addr' => $wallet['address_template'],
                ':net' => $wallet['network'],
                ':status' => 'confirmed',
                ':conf' => (int)$wallet['confirmations'],
                ':wid' => (int)$wallet['id'],
                ':now' => now_iso8601(),
            ]);

            $stmt = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, currency, meta, created_at, updated_at) VALUES (:uid, :type, :amt, :cur, :meta, :now, :now)');
            $stmt->execute([
                ':uid' => (int)$data['user_id'],
                ':type' => 'deposit',
                ':amt' => (string)$data['amount'],
                ':cur' => $data['currency'],
                ':meta' => json_encode(['txid' => $data['txid'], 'notes' => $data['notes'] ?? null]),
                ':now' => now_iso8601(),
            ]);

            $stmt = $pdo->prepare('INSERT INTO wallet_admin_changes (wallet_admin_id, admin_id, change_type, change_payload, created_at) VALUES (:wid, :aid, :type, :payload, :now)');
            $stmt->execute([':wid' => $id, ':aid' => (int)$admin['id'], ':type' => 'credit_manual', ':payload' => json_encode($data), ':now' => now_iso8601()]);

            $pdo->commit();
            json_response(['ok' => true]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            json_response(['message' => 'Failed to credit'], 500);
        }
    });
}

function user_wallets_index(PDO $pdo): void {
    $user = require_auth($pdo);
    $stmt = $pdo->prepare('SELECT uw.wallet_admin_id, wa.name, wa.currency, wa.network, uw.deposit_address, uw.deposit_tag, wa.requires_tag, wa.confirmations, wa.is_enabled, uw.address_generated FROM user_wallets uw JOIN wallet_admin wa ON wa.id = uw.wallet_admin_id WHERE uw.user_id = :uid ORDER BY uw.wallet_admin_id');
    $stmt->execute([':uid' => (int)$user['id']]);
    $rows = $stmt->fetchAll();
    // cast booleans/ints
    foreach ($rows as &$r) {
        $r['requires_tag'] = (bool)$r['requires_tag'];
        $r['is_enabled'] = (bool)$r['is_enabled'];
        $r['address_generated'] = (bool)$r['address_generated'];
        $r['confirmations'] = (int)$r['confirmations'];
    }
    json_response($rows);
}

function user_wallet_generate_address(PDO $pdo, array $params): void {
    $user = require_auth($pdo);
    $wid = (int)($params['wallet_admin_id'] ?? 0);
    // ensure user_wallet row exists
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO user_wallets (user_id, wallet_admin_id, deposit_address, deposit_tag, address_generated, status, created_at, updated_at) VALUES (:uid, :wid, NULL, NULL, 0, "active", :now, :now)');
    $stmt->execute([':uid' => (int)$user['id'], ':wid' => $wid, ':now' => now_iso8601()]);

    // fetch user_wallet and admin wallet
    $stmt = $pdo->prepare('SELECT * FROM user_wallets WHERE user_id = :uid AND wallet_admin_id = :wid');
    $stmt->execute([':uid' => (int)$user['id'], ':wid' => $wid]);
    $uw = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT * FROM wallet_admin WHERE id = :id');
    $stmt->execute([':id' => $wid]);
    $wa = $stmt->fetch();
    if (!$wa) { json_response(['message' => 'Wallet not found'], 404); return; }

    if (!(int)$wa['is_enabled'] || ($uw['status'] ?? 'active') !== 'active') {
        json_response(['message' => 'Wallet is disabled'], 403);
        return;
    }

    if (!empty($uw['deposit_address'])) {
        json_response([
            'deposit_address' => $uw['deposit_address'],
            'deposit_tag' => $uw['deposit_tag'],
            'instructions' => sprintf('Send %s (%s) to this address. Wait %d confirmations.', $wa['currency'], $wa['network'], (int)$wa['confirmations']),
        ]);
        return;
    }

    $template = (string)($wa['address_template'] ?? '');
    if (preg_match('/^(xpub|ypub|zpub|custodial:)/i', $template)) {
        $unique = substr(hash('sha256', $template . '|' . $user['id'] . '|' . time()), 0, 32);
        $address = strtoupper((string)$wa['currency']) . '_' . strtoupper($unique);
        $generated = 1;
    } else {
        $address = $template;
        $generated = 0;
    }

    $stmt = $pdo->prepare('UPDATE user_wallets SET deposit_address = :addr, address_generated = :gen, updated_at = :now WHERE id = :id');
    $stmt->execute([':addr' => $address, ':gen' => $generated, ':now' => now_iso8601(), ':id' => (int)$uw['id']]);

    json_response([
        'deposit_address' => $address,
        'deposit_tag' => $uw['deposit_tag'],
        'instructions' => sprintf('Send %s (%s) to this address. Wait %d confirmations.', $wa['currency'], $wa['network'], (int)$wa['confirmations']),
    ]);
}

function wallet_webhook_handle(PDO $pdo): void {
    json_try(function () use ($pdo) {
        $data = validate(json_input(), [
            'currency' => 'required|string|max:10',
            'network' => 'required|string|max:50',
            'txid' => 'required|string|max:191',
            'from' => 'string',
            'to' => 'required|string',
            'amount' => 'required|numeric',
            'confirmations' => 'required|integer|min:0',
            'block_time' => 'string',
            'memo' => 'string',
        ]);

        // Resolve wallet
        $stmt = $pdo->prepare('SELECT * FROM user_wallets WHERE deposit_address = :to OR deposit_tag = :memo LIMIT 1');
        $stmt->execute([':to' => $data['to'], ':memo' => $data['memo'] ?? null]);
        $uw = $stmt->fetch();

        if ($uw) {
            $stmt = $pdo->prepare('SELECT * FROM wallet_admin WHERE id = :id');
            $stmt->execute([':id' => (int)$uw['wallet_admin_id']]);
            $wa = $stmt->fetch();
        } else {
            $stmt = $pdo->prepare('SELECT * FROM wallet_admin WHERE address_template = :to LIMIT 1');
            $stmt->execute([':to' => $data['to']]);
            $wa = $stmt->fetch();
        }

        if (!$wa) {
            // Unknown address - ignore but return ok
            json_response(['ok' => true]);
            return;
        }

        $status = ((int)$data['confirmations'] >= (int)$wa['confirmations']) ? 'confirmed' : 'pending';

        $pdo->beginTransaction();
        try {
            // Find existing deposit
            $stmt = $pdo->prepare('SELECT * FROM deposits WHERE txid = :tx LIMIT 1');
            $stmt->execute([':tx' => $data['txid']]);
            $existing = $stmt->fetch();

            if ($existing) {
                $prevStatus = $existing['status'];
                $stmt = $pdo->prepare('UPDATE deposits SET user_id = :uid, amount = :amt, currency = :cur, address = :addr, network = :net, status = :status, confirmations = :conf, wallet_admin_id = :wid, updated_at = :now WHERE id = :id');
                $stmt->execute([
                    ':uid' => $uw['user_id'] ?? null,
                    ':amt' => (string)$data['amount'],
                    ':cur' => $data['currency'],
                    ':addr' => $data['to'],
                    ':net' => $data['network'],
                    ':status' => $status,
                    ':conf' => (int)$data['confirmations'],
                    ':wid' => (int)$wa['id'],
                    ':now' => now_iso8601(),
                    ':id' => (int)$existing['id'],
                ]);

                if ($status === 'confirmed' && $existing['user_id'] && $prevStatus !== 'confirmed') {
                    // Credit once
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE user_id = :uid AND type = :type AND currency = :cur AND meta LIKE :meta');
                    $stmt->execute([':uid' => (int)$existing['user_id'], ':type' => 'deposit', ':cur' => $existing['currency'], ':meta' => '%"txid":"' . $existing['txid'] . '"%']);
                    if ((int)$stmt->fetchColumn() === 0) {
                        $stmt = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, currency, meta, created_at, updated_at) VALUES (:uid, :type, :amt, :cur, :meta, :now, :now)');
                        $stmt->execute([
                            ':uid' => (int)$existing['user_id'],
                            ':type' => 'deposit',
                            ':amt' => (string)$existing['amount'],
                            ':cur' => $existing['currency'],
                            ':meta' => json_encode(['txid' => $existing['txid']]),
                            ':now' => now_iso8601(),
                        ]);
                    }
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO deposits (user_id, amount, currency, txid, address, network, status, confirmations, wallet_admin_id, created_at, updated_at) VALUES (:uid, :amt, :cur, :tx, :addr, :net, :status, :conf, :wid, :now, :now)');
                $stmt->execute([
                    ':uid' => $uw['user_id'] ?? null,
                    ':amt' => (string)$data['amount'],
                    ':cur' => $data['currency'],
                    ':tx' => $data['txid'],
                    ':addr' => $data['to'],
                    ':net' => $data['network'],
                    ':status' => $status,
                    ':conf' => (int)$data['confirmations'],
                    ':wid' => (int)$wa['id'],
                    ':now' => now_iso8601(),
                ]);
            }

            $pdo->commit();
            json_response(['ok' => true]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            json_response(['message' => 'Webhook failed'], 500);
        }
    });
}

// Admin settings
function admin_settings_get(PDO $pdo): void {
    require_admin($pdo);
    $all = Config::getAll();
    json_response([
        'nowpayments_api_key' => $all['nowpayments_api_key'] ?? null,
        'ipn_secret_key' => $all['ipn_secret_key'] ?? null,
    ]);
}

function admin_settings_update(PDO $pdo): void {
    require_admin($pdo);
    json_try(function () {
        $data = json_input();
        $allowed = [];
        if (isset($data['nowpayments_api_key'])) $allowed['nowpayments_api_key'] = (string)$data['nowpayments_api_key'];
        if (isset($data['ipn_secret_key'])) $allowed['ipn_secret_key'] = (string)$data['ipn_secret_key'];
        $saved = Config::setMany($allowed);
        json_response(['ok' => true, 'settings' => [
            'nowpayments_api_key' => $saved['nowpayments_api_key'] ?? null,
            'ipn_secret_key' => $saved['ipn_secret_key'] ?? null,
        ]]);
    });
}

// NOWPayments IPN
function nowpayments_ipn(PDO $pdo): void {
    $raw = file_get_contents('php://input') ?: '';
    $sig = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';
    if (!NowPayments::verifyIpn($raw, $sig)) {
        json_response(['message' => 'Invalid signature'], 400);
        return;
    }
    $payload = json_decode($raw, true) ?: [];
    $status = strtolower((string)($payload['payment_status'] ?? ''));
    $txid = (string)($payload['transaction_id'] ?? $payload['payment_id'] ?? '');
    $payAmount = (string)($payload['pay_amount'] ?? '0');
    $payCurrency = strtoupper((string)($payload['pay_currency'] ?? ''));

    // Resolve wallet by currency and use_nowpayments flag
    $stmt = $pdo->prepare('SELECT * FROM wallet_admin WHERE UPPER(currency) = :cur AND use_nowpayments = 1 LIMIT 1');
    $stmt->execute([':cur' => $payCurrency]);
    $wa = $stmt->fetch();
    if (!$wa) { json_response(['ok' => true]); return; }

    $finalStatus = in_array($status, ['finished','confirmed','completed','paid'], true) ? 'confirmed' : 'pending';

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM deposits WHERE txid = :tx LIMIT 1');
        $stmt->execute([':tx' => $txid]);
        $existing = $stmt->fetch();
        if ($existing) {
            $prev = $existing['status'];
            $stmt = $pdo->prepare('UPDATE deposits SET amount = :amt, currency = :cur, address = :addr, network = :net, status = :status, confirmations = :conf, wallet_admin_id = :wid, updated_at = :now WHERE id = :id');
            $stmt->execute([
                ':amt' => $payAmount,
                ':cur' => $payCurrency,
                ':addr' => 'NOWPAYMENTS',
                ':net' => $wa['network'],
                ':status' => $finalStatus,
                ':conf' => 0,
                ':wid' => (int)$wa['id'],
                ':now' => now_iso8601(),
                ':id' => (int)$existing['id'],
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO deposits (user_id, amount, currency, txid, address, network, status, confirmations, wallet_admin_id, created_at, updated_at) VALUES (NULL, :amt, :cur, :tx, :addr, :net, :status, 0, :wid, :now, :now)');
            $stmt->execute([
                ':amt' => $payAmount,
                ':cur' => $payCurrency,
                ':tx' => $txid,
                ':addr' => 'NOWPAYMENTS',
                ':net' => $wa['network'],
                ':status' => $finalStatus,
                ':wid' => (int)$wa['id'],
                ':now' => now_iso8601(),
            ]);
        }
        $pdo->commit();
        json_response(['ok' => true]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['message' => 'IPN failed'], 500);
    }
}

// Define routes
route('GET', '/install', fn($pdo) => handle_install_get($pdo));
route('POST', '/install', fn($pdo) => handle_install_post($pdo));

route('POST', '/api/login', fn($pdo) => handle_login($pdo));
route('POST', '/api/logout', fn($pdo) => handle_logout($pdo));
route('GET', '/api/user', fn($pdo) => handle_me($pdo));

// Settings routes
route('GET', '/api/admin/settings', fn($pdo) => admin_settings_get($pdo));
route('POST', '/api/admin/settings', fn($pdo) => admin_settings_update($pdo));

route('GET', '/api/admin/wallets', fn($pdo) => admin_wallet_index($pdo));
route('POST', '/api/admin/wallets', fn($pdo) => admin_wallet_store($pdo));
route('PUT', '/api/admin/wallets/{id}', fn($pdo, $p) => admin_wallet_update($pdo, $p));
route('PATCH', '/api/admin/wallets/{id}/toggle', fn($pdo, $p) => admin_wallet_toggle($pdo, $p));
route('POST', '/api/admin/wallets/{id}/assign', fn($pdo, $p) => admin_wallet_assign($pdo, $p));
route('POST', '/api/admin/wallets/{id}/credit-manual', fn($pdo, $p) => admin_wallet_credit_manual($pdo, $p));

route('GET', '/api/user/wallets', fn($pdo) => user_wallets_index($pdo));
route('POST', '/api/user/wallets/{wallet_admin_id}/generate-address', fn($pdo, $p) => user_wallet_generate_address($pdo, $p));

// Generic webhook retained for address-based flows
route('POST', '/api/wallets/webhook', fn($pdo) => wallet_webhook_handle($pdo));
// NOWPayments IPN endpoint
route('POST', '/api/nowpayments/ipn', fn($pdo) => nowpayments_ipn($pdo));

// Dispatch
if ($method === 'POST' && ($_POST['_method'] ?? '') !== '') {
    $method = strtoupper($_POST['_method']);
}

dispatch($pdo, $method, rtrim($path, '/') === '' ? '/' : rtrim($path, '/'));