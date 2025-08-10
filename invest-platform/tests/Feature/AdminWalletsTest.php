<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminWalletsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        $this->artisan('migrate');
    }

    public function test_admin_can_create_update_toggle_assign_wallet_and_user_can_generate_address(): void
    {
        $investor = User::factory()->create();
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin, ['*']);

        $res = $this->postJson('/api/admin/wallets', [
            'name' => 'USDT ERC20',
            'currency' => 'USDT',
            'network' => 'erc20',
            'address_template' => 'xpubTEST',
            'requires_tag' => false,
            'tag_label' => null,
            'confirmations' => 12,
            'icon_url' => 'https://cdn.example.com/icons/usdt.svg',
            'is_enabled' => true,
        ]);
        $res->assertCreated();
        $walletId = $res->json('id');

        $this->putJson("/api/admin/wallets/{$walletId}", ['confirmations' => 10])->assertOk();
        $this->patchJson("/api/admin/wallets/{$walletId}/toggle", ['is_enabled' => false])->assertOk();
        $this->postJson("/api/admin/wallets/{$walletId}/assign")->assertOk();

        Sanctum::actingAs($investor, ['*']);
        $this->getJson('/api/user/wallets')->assertOk();
        $this->postJson("/api/user/wallets/{$walletId}/generate-address")->assertStatus(403);

        // Re-enable and generate
        Sanctum::actingAs($admin, ['*']);
        $this->patchJson("/api/admin/wallets/{$walletId}/toggle", ['is_enabled' => true])->assertOk();
        Sanctum::actingAs($investor, ['*']);
        $gen = $this->postJson("/api/user/wallets/{$walletId}/generate-address");
        $gen->assertOk();
        $addr = $gen->json('deposit_address');

        $this->postJson('/api/wallets/webhook', [
            'currency' => 'USDT',
            'network' => 'erc20',
            'txid' => '0xabc123',
            'from' => '0xfrom',
            'to' => $addr,
            'amount' => '150.00',
            'confirmations' => 12,
            'block_time' => now()->toISOString(),
            'memo' => null,
        ])->assertOk();

        $this->assertDatabaseHas('deposits', ['txid' => '0xabc123', 'status' => 'confirmed']);
    }

    public function test_admin_manual_credit(): void
    {
        $investor = User::factory()->create();
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin, ['*']);

        $walletId = $this->postJson('/api/admin/wallets', [
            'name' => 'BTC',
            'currency' => 'BTC',
            'network' => 'bitcoin',
            'address_template' => 'bc1qglobal',
            'requires_tag' => false,
            'tag_label' => null,
            'confirmations' => 2,
            'icon_url' => null,
            'is_enabled' => true,
        ])->json('id');

        $this->postJson("/api/admin/wallets/{$walletId}/credit-manual", [
            'user_id' => $investor->id,
            'amount' => '0.01000000',
            'currency' => 'BTC',
            'txid' => 'manual-1',
            'notes' => 'recon',
        ])->assertOk();

        $this->assertDatabaseHas('deposits', ['txid' => 'manual-1', 'status' => 'confirmed']);
        $this->assertDatabaseHas('transactions', ['type' => 'deposit', 'currency' => 'BTC']);
    }
}