<?php

namespace Tests\Feature\Integration;

use App\Models\InventoryOutboxEvent;
use App\Services\Integration\Inventory\StockEventDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class StockEventDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private function event(string $sku, int $sale, float $quantity): InventoryOutboxEvent
    {
        return InventoryOutboxEvent::create([
            'event_type' => $quantity < 0 ? 'sale.completed' : 'sale.refunded',
            'sale_transaction_id' => $sale, 'product_id' => $sku === 'BEEF' ? 1 : 2,
            'inventory_sku' => $sku, 'quantity' => $quantity, 'payload' => [],
            'status' => 'pending', 'attempts' => 0, 'available_at' => now(), 'created_at' => now(),
        ]);
    }

    public function test_rejected_product_does_not_block_other_products_but_preserves_its_own_order(): void
    {
        config(['services.inventory.base_url' => 'https://inventory.test', 'services.inventory.token' => 'test', 'services.pos_instance_id' => 'pos-test']);
        $beef = $this->event('BEEF', 1, -5);
        $coffee = $this->event('COFFEE', 2, -20);
        $laterBeef = $this->event('BEEF', 1, 5);
        Http::fake(fn ($request) => $request['sku'] === 'BEEF'
            ? Http::response(['message' => 'Insufficient stock'], 422)
            : Http::response(['accepted' => true]));

        $failed = false;
        try { app(StockEventDispatcher::class)->dispatch(); } catch (RuntimeException) { $failed = true; }
        $this->assertTrue($failed);
        $this->assertNotNull($coffee->fresh()->processed_at);
        $this->assertNull($beef->fresh()->processed_at);
        $this->assertSame(1, $beef->fresh()->attempts);
        $this->assertNull($laterBeef->fresh()->processed_at);
        $this->assertSame(0, $laterBeef->fresh()->attempts);
        Http::assertSentCount(2);
    }

    public function test_connection_failure_is_recorded_and_other_products_are_attempted(): void
    {
        config(['services.inventory.base_url' => 'https://inventory.test', 'services.inventory.token' => 'test', 'services.pos_instance_id' => 'pos-test']);
        $beef = $this->event('BEEF', 1, -5);
        $coffee = $this->event('COFFEE', 2, -20);
        Http::fake(function ($request) {
            if ($request['sku'] === 'BEEF') throw new ConnectionException('Timeout');
            return Http::response(['accepted' => true]);
        });
        try { app(StockEventDispatcher::class)->dispatch(); } catch (RuntimeException) {}
        $this->assertNotNull($coffee->fresh()->processed_at);
        $this->assertSame(1, $beef->fresh()->attempts);
        $this->assertNotNull($beef->fresh()->last_error);
    }
}
