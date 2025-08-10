<?php
declare(strict_types=1);

use App\Core\Env;
use App\Core\Router;
use App\Controllers\AdminWalletController;
use App\Controllers\UserWalletController;
use App\Controllers\WalletWebhookController;

require __DIR__ . '/../vendor/autoload.php';

Env::load(__DIR__ . '/../');

$router = new Router();

// Admin routes (protected by X-Admin-Key header)
$router->post('/admin/wallets', [AdminWalletController::class, 'create']);
$router->put('/admin/wallets/{id}', [AdminWalletController::class, 'update']);
$router->patch('/admin/wallets/{id}/toggle', [AdminWalletController::class, 'toggle']);
$router->post('/admin/wallets/{id}/assign', [AdminWalletController::class, 'assignAll']);
$router->post('/admin/wallets/{id}/credit-manual', [AdminWalletController::class, 'creditManual']);

// User routes (protected by X-User-Id header for demo; replace with sessions later)
$router->get('/user/wallets', [UserWalletController::class, 'list']);
$router->post('/user/wallets/{wallet_admin_id}/generate-address', [UserWalletController::class, 'generateAddress']);

// Webhook
$router->post('/wallets/webhook', [WalletWebhookController::class, 'handle']);

$router->dispatch();