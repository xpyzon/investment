<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WalletAdminPageController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/wallets', [WalletAdminPageController::class, 'index'])->name('admin.wallets.index');
    Route::get('/wallets/create', [WalletAdminPageController::class, 'create'])->name('admin.wallets.create');
});
