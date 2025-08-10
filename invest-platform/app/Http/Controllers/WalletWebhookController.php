<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\UserWallet;
use App\Models\WalletAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // TODO: verify HMAC/signature in production
        $data = $request->validate([
            'currency' => 'required|string|max:10',
            'network' => 'required|string|max:50',
            'txid' => 'required|string|max:191',
            'from' => 'nullable|string',
            'to' => 'required|string',
            'amount' => 'required',
            'confirmations' => 'required|integer|min:0',
            'block_time' => 'nullable|string',
            'memo' => 'nullable|string',
        ]);

        $userWallet = UserWallet::where('deposit_address', $data['to'])
            ->orWhere('deposit_tag', $data['memo'] ?? null)
            ->first();

        $adminWallet = null;
        if (!$userWallet) {
            $adminWallet = WalletAdmin::where('address_template', $data['to'])->first();
        } else {
            $adminWallet = $userWallet->walletAdmin;
        }

        if (!$adminWallet) {
            // unknown address - log and ignore (or create unmatched deposit record)
            Log::warning('Webhook deposit for unknown address', $data);
            return response()->json(['ok' => true]);
        }

        $status = ($data['confirmations'] >= $adminWallet->confirmations) ? 'confirmed' : 'pending';

        DB::transaction(function () use ($data, $userWallet, $adminWallet, $status) {
            $deposit = Deposit::firstOrNew(['txid' => $data['txid']]);
            $deposit->user_id = $userWallet?->user_id;
            $deposit->amount = (string)$data['amount'];
            $deposit->currency = $data['currency'];
            $deposit->address = $data['to'];
            $deposit->network = $data['network'];
            $deposit->status = $status;
            $deposit->confirmations = $data['confirmations'];
            $deposit->wallet_admin_id = $adminWallet->id;
            $deposit->save();

            if ($deposit->status === 'confirmed' && $deposit->user_id && !$deposit->wasRecentlyCreated) {
                // credit ledger only once when moving from pending->confirmed
                Transaction::firstOrCreate([
                    'user_id' => $deposit->user_id,
                    'type' => 'deposit',
                    'currency' => $deposit->currency,
                    'meta' => json_encode(['txid' => $deposit->txid]),
                ], [
                    'amount' => $deposit->amount,
                ]);
            }
        });

        return response()->json(['ok' => true]);
    }
}