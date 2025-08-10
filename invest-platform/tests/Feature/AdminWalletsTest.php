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
        // Create investor first so assignment includes them
        $investor = User::factory()->create();

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin, ['*']);

        // Create wallet
        $payload = [
            'name' => 'USDT ERC20',
            'currency' => 'USDT',
            'network' => 'erc20',
            'address_template' => '0xGLOBALADDRESS',
            'requires_tag' => false,
            'tag_label' => null,
            'confirmations' => 12,
            'icon_url' => 'https://cdn.example.com/icons/usdt.svg',
            'is_enabled' => true,
        ];
        $res = $this->postJson('/api/admin/wallets', $payload);
        $res->assertCreated();
        $walletId = $res->json('id');
        $this->assertNotNull($walletId);

        // Update wallet
        $res = $this->putJson("/api/admin/wallets/{$walletId}", [
            'address_template' => 'xpubTEST',
        ]);
        $res->assertOk();

        // Toggle off
        $res = $this->patchJson("/api/admin/wallets/{$walletId}/toggle", ['is_enabled' => false]);
        $res->assertOk();

        // Assign to all users (redundant but fine)
        $res = $this->postJson("/api/admin/wallets/{$walletId}/assign");
        $res->assertOk();

        // Investor lists wallets
        Sanctum::actingAs($investor, ['*']);
        $res = $this->getJson('/api/user/wallets');
        $res->assertOk();
        $data = $res->json();
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
        $res->assertJsonStructure([
            [
                'wallet_admin_id', 'name', 'currency', 'network', 'deposit_address', 'deposit_tag', 'requires_tag', 'confirmations', 'is_enabled', 'address_generated'
            ]
        ]);

        // Generate a unique address (xpub template triggers unique)
        $res = $this->postJson("/api/user/wallets/{$walletId}/generate-address");
        $res->assertOk();
        $res->assertJsonStructure(['deposit_address', 'deposit_tag', 'instructions']);

        // Webhook confirm deposit
        $payload = [
            'currency' => 'USDT',
            'network' => 'erc20',
            'txid' => '0xabc123',
            'from' => '0xfrom',
            'to' => $res->json('deposit_address'),
            'amount' => '150.00',
            'confirmations' => 12,
            'block_time' => now()->toISOString(),
            'memo' => null,
        ];
        $res2 = $this->postJson('/api/wallets/webhook', $payload);
        $res2->assertOk();

        $this->assertDatabaseHas('deposits', [
            'txid' => '0xabc123',
            'status' => 'confirmed',
        ]);
    }
}