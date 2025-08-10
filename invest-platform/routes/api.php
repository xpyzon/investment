<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WalletAdminController;
use App\Http\Controllers\User\UserWalletController;
use App\Http\Controllers\WalletWebhookController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/wallets', [WalletAdminController::class, 'index']);
    Route::post('/wallets', [WalletAdminController::class, 'store']);
    Route::put('/wallets/{id}', [WalletAdminController::class, 'update']);
    Route::patch('/wallets/{id}/toggle', [WalletAdminController::class, 'toggle']);
    Route::post('/wallets/{id}/assign', [WalletAdminController::class, 'assign']);
    Route::post('/wallets/{id}/credit-manual', [WalletAdminController::class, 'creditManual']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user/wallets', [UserWalletController::class, 'index']);
    Route::post('/user/wallets/{wallet_admin_id}/generate-address', [UserWalletController::class, 'generateAddress']);
});

Route::post('/wallets/webhook', [WalletWebhookController::class, 'handle']);
