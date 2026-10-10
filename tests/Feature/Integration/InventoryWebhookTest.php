<?php

namespace Tests\Feature\Integration;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InventoryWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.inventory_webhook_token' => 'receiver-token',
            'services.inventory.base_url' => 'https://inventory.test', 'services.inventory.token' => 'sender-token',
            'services.pos_instance_id' => 'pos-test']);
        Http::preventStrayRequests();
    }

    public function test_authentication_validation_and_duplicate_conflict_protection(): void
    {
        $url = '/api/v1/inventory/product-updated';
        $event = ['event_id' => 'evt-1', 'sku' => 'SKU-1'];
        $this->postJson($url, $event)->assertUnauthorized();
        $this->withToken('wrong')->postJson($url, $event)->assertUnauthorized();
        $this->assertSame(0, DB::table('inventory_webhook_inbox')->count());
        $this->withToken('receiver-token')->postJson($url, $event)->assertStatus(202)->assertJsonPath('duplicate', false);
        $this->withToken('receiver-token')->postJson($url, $event)->assertOk()->assertJsonPath('duplicate', true);
        $this->withToken('receiver-token')->postJson($url, [...$event, 'sku' => 'SKU-2'])->assertStatus(409);
        $this->withToken('receiver-token')->postJson($url, ['event_id' => 'evt-2'])->assertUnprocessable();
        $this->assertSame(1, DB::table('inventory_webhook_inbox')->count());
        config(['services.inventory_webhook_token' => null]);
        $this->withToken('receiver-token')->postJson($url, $event)->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_worker_fetches_authoritative_stock_and_retains_failed_notifications(): void
    {
        $this->withToken('receiver-token')->postJson('/api/v1/inventory/product-updated', [
            'event_id' => 'evt-1', 'sku' => 'SKU-1', 'stock' => 9999,
        ])->assertStatus(202);
        Http::fake(['https://inventory.test/api/v1/catalog/products*' => Http::response([], 503)]);
        $this->artisan('integrations:webhooks --once')->assertExitCode(1);
        $event = DB::table('inventory_webhook_inbox')->first();
        $this->assertNull($event->processed_at);
        $this->assertSame(1, $event->attempts);
        $this->assertSame(0, Product::count());
        DB::table('inventory_webhook_inbox')->update(['available_at' => now()]);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['https://inventory.test/api/v1/catalog/products*' => Http::response([
            'data' => [['external_id' => 'inventory:sku:SKU-1', 'sku' => 'SKU-1', 'name' => 'Rice',
                'barcode' => '4006381333931', 'unit_price' => 50, 'cost_price' => 30, 'reorder_point' => 2,
                'status' => 'Active', 'on_hand' => 10]],
            'meta' => ['page' => 1, 'last_page' => 1, 'total' => 1],
        ])]);
        $this->artisan('integrations:webhooks --once')->assertExitCode(0);
        $this->assertNotNull(DB::table('inventory_webhook_inbox')->first()->processed_at);
        $this->assertEquals(10, Product::first()->inventory->stock_quantity);
        $this->artisan('integrations:webhooks --once')->assertExitCode(0);
        $this->assertSame(1, Product::count());
    }
}
