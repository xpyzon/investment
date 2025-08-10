<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletAdmin;
use App\Models\UserWallet;
use App\Models\User;
use App\Models\Deposit;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class WalletAdminController extends Controller
{
    public function index()
    {
        return WalletAdmin::query()->orderByDesc('id')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'currency' => 'required|string|max:10',
            'network' => 'required|string|max:50',
            'address_template' => 'nullable|string',
            'requires_tag' => 'boolean',
            'tag_label' => 'nullable|string|max:50',
            'confirmations' => 'required|integer|min:0|max:100',
            'icon_url' => 'nullable|url|max:255',
            'is_enabled' => 'boolean',
        ]);

        $adminUser = $request->user();
        $data['created_by'] = $adminUser->id;
        $data['updated_by'] = $adminUser->id;

        $wallet = null;
        DB::transaction(function () use ($data, &$wallet) {
            $wallet = WalletAdmin::create($data);
            $this->assignWalletToAllUsers($wallet->id);
            $this->recordChange($wallet->id, $data['created_by'], 'create', $data);
        });

        Event::dispatch('wallet.created', $wallet);

        return response()->json($wallet, 201);
    }

    public function update(Request $request, int $id)
    {
        $wallet = WalletAdmin::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:191',
            'currency' => 'sometimes|string|max:10',
            'network' => 'sometimes|string|max:50',
            'address_template' => 'nullable|string',
            'requires_tag' => 'boolean',
            'tag_label' => 'nullable|string|max:50',
            'confirmations' => 'sometimes|integer|min:0|max:100',
            'icon_url' => 'nullable|url|max:255',
            'is_enabled' => 'boolean',
        ]);

        $adminUser = $request->user();
        $data['updated_by'] = $adminUser->id;

        DB::transaction(function () use ($wallet, $data) {
            $wallet->fill($data);
            $wallet->save();

            // propagate updates
            if (array_key_exists('is_enabled', $data)) {
                UserWallet::where('wallet_admin_id', $wallet->id)
                    ->update(['status' => $wallet->is_enabled ? 'active' : 'disabled']);
            }
            if (array_key_exists('address_template', $data)) {
                // global address mode: update all user deposit_address if address_generated==false
                UserWallet::where('wallet_admin_id', $wallet->id)
                    ->where('address_generated', false)
                    ->update(['deposit_address' => $wallet->address_template]);
            }

            $this->recordChange($wallet->id, $data['updated_by'], 'update', $data);
        });

        Event::dispatch('wallet.updated', $wallet);

        return response()->json($wallet);
    }

    public function toggle(Request $request, int $id)
    {
        $wallet = WalletAdmin::findOrFail($id);
        $data = $request->validate([
            'is_enabled' => 'required|boolean'
        ]);

        DB::transaction(function () use ($wallet, $data, $request) {
            $wallet->is_enabled = $data['is_enabled'];
            $wallet->updated_by = $request->user()->id;
            $wallet->save();

            UserWallet::where('wallet_admin_id', $wallet->id)
                ->update(['status' => $wallet->is_enabled ? 'active' : 'disabled']);

            $this->recordChange($wallet->id, $request->user()->id, 'toggle', $data);
        });

        Event::dispatch('wallet.toggled', $wallet);

        return response()->json(['ok' => true]);
    }

    public function assign(int $id)
    {
        $count = $this->assignWalletToAllUsers($id);
        Event::dispatch('wallet.assignments.updated', ['wallet_admin_id' => $id, 'count' => $count]);
        return response()->json(['assigned' => $count]);
    }

    public function creditManual(Request $request, int $id)
    {
        $wallet = WalletAdmin::findOrFail($id);
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.00000001',
            'currency' => 'required|string|max:10',
            'txid' => 'required|string|max:191|unique:deposits,txid',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($wallet, $data, $request) {
            $deposit = Deposit::create([
                'user_id' => $data['user_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'txid' => $data['txid'],
                'address' => $wallet->address_template,
                'network' => $wallet->network,
                'status' => 'confirmed',
                'fees' => 0,
                'confirmations' => $wallet->confirmations,
                'wallet_admin_id' => $wallet->id,
            ]);

            Transaction::create([
                'user_id' => $deposit->user_id,
                'type' => 'deposit',
                'amount' => $deposit->amount,
                'currency' => $deposit->currency,
                'meta' => [
                    'txid' => $deposit->txid,
                    'notes' => $request->input('notes'),
                ],
            ]);

            $this->recordChange($wallet->id, $request->user()->id, 'credit_manual', $data);
        });

        return response()->json(['ok' => true]);
    }

    protected function assignWalletToAllUsers(int $walletAdminId): int
    {
        $users = User::query()->select('id')->get();
        $count = 0;
        foreach ($users as $user) {
            $created = UserWallet::firstOrCreate([
                'user_id' => $user->id,
                'wallet_admin_id' => $walletAdminId,
            ], [
                'deposit_address' => null,
                'deposit_tag' => null,
                'address_generated' => false,
                'status' => 'active',
            ]);
            if ($created->wasRecentlyCreated) {
                $count++;
            }
        }
        return $count;
    }

    protected function recordChange(int $walletAdminId, int $adminId, string $type, array $payload = []): void
    {
        DB::table('wallet_admin_changes')->insert([
            'wallet_admin_id' => $walletAdminId,
            'admin_id' => $adminId,
            'change_type' => $type,
            'change_payload' => json_encode($payload),
            'created_at' => now(),
        ]);
    }
}