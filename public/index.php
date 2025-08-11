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
use App\Controllers\UserUiController;
use App\Controllers\MarketController;

require __DIR__ . '/../vendor/autoload.php';

Env::load(__DIR__ . '/../');

$router = new Router();

// Public UI routes
$router->get('/', [UserUiController::class, 'loginForm']);
$router->get('/login', [UserUiController::class, 'loginForm']);
$router->post('/login', [UserUiController::class, 'loginSubmit']);
$router->post('/logout', [UserUiController::class, 'logout']);
$router->get('/dashboard', [UserUiController::class, 'dashboard']);
$router->get('/products', [UserUiController::class, 'products']);
$router->get('/invest/{id}', [UserUiController::class, 'investForm']);
$router->post('/invest/submit', [UserUiController::class, 'investSubmit']);
$router->get('/wallets', [UserUiController::class, 'wallets']);
$router->post('/wallets/{id}/generate', [UserUiController::class, 'walletsGenerate']);
$router->get('/withdrawals', [UserUiController::class, 'withdrawals']);
$router->post('/withdrawals', [UserUiController::class, 'withdrawalsSubmit']);
$router->get('/account', [UserUiController::class, 'account']);
$router->post('/account/password', [UserUiController::class, 'accountPassword']);
$router->get('/account/2fa/setup', [UserUiController::class, 'account2faSetup']);
$router->post('/account/2fa/enable', [UserUiController::class, 'account2faEnable']);
$router->post('/account/2fa/disable', [UserUiController::class, 'account2faDisable']);
$router->get('/portfolio', [UserUiController::class, 'portfolio']);
$router->get('/market', [UserUiController::class, 'market']);

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
$router->get('/api/products', [InvestmentController::class, 'products']);
$router->post('/api/invest', [InvestmentController::class, 'invest']);
$router->post('/api/withdrawals/request', [WithdrawalController::class, 'request']);
$router->get('/api/market/prices', [MarketController::class, 'prices']);
$router->get('/api/market/chart', [MarketController::class, 'chart']);

// Admin withdrawals
$router->get('/admin/withdrawals', [WithdrawalController::class, 'adminList']);
$router->post('/admin/withdrawals/{id}/approve', [WithdrawalController::class, 'adminApprove']);
$router->post('/admin/withdrawals/{id}/reject', [WithdrawalController::class, 'adminReject']);

// Webhook
$router->post('/wallets/webhook', [WalletWebhookController::class, 'handle']);

$router->dispatch();