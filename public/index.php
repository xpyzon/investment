<?php
declare(strict_types=1);

use App\Core\Env;
use App\Core\Router;
use App\Controllers\AdminWalletController;
use App\Controllers\UserWalletController;
use App\Controllers\WalletWebhookController;
use App\Controllers\AdminUiController;
use App\Controllers\InvestmentController;
use App\Controllers\WithdrawalController;

require __DIR__ . '/../vendor/autoload.php';

Env::load(__DIR__ . '/../');

$router = new Router();

// Admin routes (API)
$router->post('/admin/wallets', [AdminWalletController::class, 'create']);
$router->put('/admin/wallets/{id}', [AdminWalletController::class, 'update']);
$router->patch('/admin/wallets/{id}/toggle', [AdminWalletController::class, 'toggle']);
$router->post('/admin/wallets/{id}/assign', [AdminWalletController::class, 'assignAll']);
$router->post('/admin/wallets/{id}/credit-manual', [AdminWalletController::class, 'creditManual']);

// Admin UI (Tailwind)
$router->get('/admin/ui/wallets', [AdminUiController::class, 'walletsIndex']);
$router->get('/admin/ui/wallets/create', [AdminUiController::class, 'walletsCreateForm']);
$router->post('/admin/ui/wallets/create', [AdminUiController::class, 'walletsCreate']);
$router->get('/admin/ui/wallets/{id}/edit', [AdminUiController::class, 'walletsEditForm']);
$router->post('/admin/ui/wallets/{id}/edit', [AdminUiController::class, 'walletsUpdate']);
$router->post('/admin/ui/wallets/{id}/toggle', [AdminUiController::class, 'walletsToggle']);
$router->post('/admin/ui/wallets/{id}/assign', [AdminUiController::class, 'walletsAssignAll']);

// User routes (API)
$router->get('/user/wallets', [UserWalletController::class, 'list']);
$router->post('/user/wallets/{wallet_admin_id}/generate-address', [UserWalletController::class, 'generateAddress']);
$router->get('/products', [InvestmentController::class, 'products']);
$router->post('/invest', [InvestmentController::class, 'invest']);
$router->post('/withdrawals/request', [WithdrawalController::class, 'request']);

// Admin withdrawals
$router->get('/admin/withdrawals', [WithdrawalController::class, 'adminList']);
$router->post('/admin/withdrawals/{id}/approve', [WithdrawalController::class, 'adminApprove']);
$router->post('/admin/withdrawals/{id}/reject', [WithdrawalController::class, 'adminReject']);

// Webhook
$router->post('/wallets/webhook', [WalletWebhookController::class, 'handle']);

$router->dispatch();