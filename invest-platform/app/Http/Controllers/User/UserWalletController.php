<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserWallet;
use App\Models\WalletAdmin;
use Illuminate\Http\Request;

class UserWalletController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $wallets = UserWallet::with('walletAdmin')
            ->where('user_id', $userId)
            ->orderBy('wallet_admin_id')
            ->get()
            ->map(function (UserWallet $uw) {
                return [
                    'wallet_admin_id' => $uw->wallet_admin_id,
                    'name' => $uw->walletAdmin->name,
                    'currency' => $uw->walletAdmin->currency,
                    'network' => $uw->walletAdmin->network,
                    'deposit_address' => $uw->deposit_address,
                    'deposit_tag' => $uw->deposit_tag,
                    'requires_tag' => $uw->walletAdmin->requires_tag,
                    'confirmations' => $uw->walletAdmin->confirmations,
                    'is_enabled' => $uw->walletAdmin->is_enabled,
                    'address_generated' => (bool) $uw->address_generated,
                ];
            });

        return response()->json($wallets);
    }

    public function generateAddress(Request $request, int $walletAdminId)
    {
        $user = $request->user();
        $uw = UserWallet::firstOrCreate([
            'user_id' => $user->id,
            'wallet_admin_id' => $walletAdminId,
        ]);

        $adminWallet = WalletAdmin::findOrFail($walletAdminId);

        if (!$adminWallet->is_enabled || $uw->status !== 'active') {
            return response()->json(['message' => 'Wallet is disabled'], 403);
        }

        if ($uw->deposit_address) {
            return response()->json([
                'deposit_address' => $uw->deposit_address,
                'deposit_tag' => $uw->deposit_tag,
                'instructions' => sprintf('Send %s (%s) to this address. Wait %d confirmations.', $adminWallet->currency, $adminWallet->network, $adminWallet->confirmations),
            ]);
        }

        $template = (string) $adminWallet->address_template;
        if (str_starts_with($template, 'xpub') || str_starts_with($template, 'ypub') || str_starts_with($template, 'zpub') || str_starts_with($template, 'custodial:')) {
            $unique = substr(hash('sha256', $template.'|'.$user->id.'|'.now()->timestamp), 0, 32);
            $address = strtoupper($adminWallet->currency)."_".$unique;
            $uw->deposit_address = $address;
            $uw->address_generated = true;
            $uw->save();
        } else {
            $uw->deposit_address = $adminWallet->address_template;
            $uw->address_generated = false;
            $uw->save();
        }

        return response()->json([
            'deposit_address' => $uw->deposit_address,
            'deposit_tag' => $uw->deposit_tag,
            'instructions' => sprintf('Send %s (%s) to this address. Wait %d confirmations.', $adminWallet->currency, $adminWallet->network, $adminWallet->confirmations),
        ]);
    }
}