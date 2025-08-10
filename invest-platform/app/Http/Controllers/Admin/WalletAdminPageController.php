<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletAdmin;
use Illuminate\Http\Request;

class WalletAdminPageController extends Controller
{
    public function index()
    {
        $wallets = WalletAdmin::orderByDesc('id')->get();
        return view('admin.wallets.index', compact('wallets'));
    }

    public function create()
    {
        return view('admin.wallets.create');
    }
}