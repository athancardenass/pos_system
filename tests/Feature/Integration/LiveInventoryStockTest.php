<?php

namespace Tests\Feature\Integration;

use App\Models\Inventory;
use App\Models\Product;
use App\Services\SaleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LiveInventoryStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['services.inventory.base_url' => 'https://inventory.test', 'services.inventory.token' => 'test']);
        Http::preventStrayRequests();
    }

    private function product(): Product
    {
        $product = Product::firstOrFail();
        Inventory::updateOrCreate(['product_id' => $product->product_id], ['stock_quantity' => 95]);
        DB::table('inventory_product_mappings')->insert(['pos_product_id' => $product->product_id, 'inventory_sku' => 'BEEF', 'created_at' => now(), 'updated_at' => now()]);
        return $product;
    }

    private function remoteStock(float $stock): void
    {
        Http::fake(['inventory.test/*' => Http::response(['data' => [[
            'sku' => 'BEEF', 'external_id' => 'inventory:sku:BEEF', 'status' => 'Active', 'on_hand' => $stock,
        ]], 'meta' => ['page' => 1, 'last_page' => 1, 'total' => 1]])]);
    }

    public function test_register_uses_inventory_zero_instead_of_stale_local_95(): void
    {
        $product = $this->product();
        $this->remoteStock(0);
        $this->assertEquals(0, app(SaleService::class)->getProductStockSnapshot()[$product->product_id]['stock']);
        $this->assertEquals(95, $product->fresh()->inventory->stock_quantity);
    }

    public function test_checkout_rejects_inventory_zero_without_creating_a_sale(): void
    {
        $product = $this->product();
        $this->remoteStock(0);
        $cashier = \App\Models\Employee::where('username', 'cashier')->firstOrFail();
        $before = DB::table('sale_transaction')->count();
        $this->actingAs($cashier)->post(route('pos.store'), [
            'payment_method' => 'cash', 'amount_paid' => 10000,
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
        ])->assertSessionHasErrors('items');
        $this->assertSame($before, DB::table('sale_transaction')->count());
        $this->assertEquals(95, $product->fresh()->inventory->stock_quantity);
    }

    public function test_unavailable_inventory_does_not_return_stale_stock(): void
    {
        $this->product();
        Http::fake(['inventory.test/*' => Http::response([], 503)]);
        $this->expectException(ValidationException::class);
        app(SaleService::class)->getProductStockSnapshot();
    }

    public function test_card_submission_cannot_reserve_stale_stock(): void
    {
        $product = $this->product();
        $this->remoteStock(0);
        $cashier = \App\Models\Employee::where('username', 'cashier')->firstOrFail();
        $this->expectException(ValidationException::class);
        app(\App\Services\CardVerificationService::class)->submit([
            'payment_provider' => 'Visa', 'reference_number' => 'LIVE-CARD-001', 'amount_paid' => 100,
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
        ], $cashier->employee_id);
    }

    public function test_ewallet_submission_cannot_reserve_stale_stock(): void
    {
        $product = $this->product();
        $this->remoteStock(0);
        $cashier = \App\Models\Employee::where('username', 'cashier')->firstOrFail();
        $this->expectException(ValidationException::class);
        app(\App\Services\EwalletVerificationService::class)->submit([
            'payment_provider' => 'GCash', 'reference_number' => 'LIVE-WALLET-001', 'amount_paid' => 100,
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
        ], $cashier->employee_id);
    }

    public function test_live_snapshot_subtracts_pending_sales_without_acknowledging_them(): void
    {
        $product = $this->product();
        $this->remoteStock(100);
        $cashier = \App\Models\Employee::where('username', 'cashier')->firstOrFail();
        $sale = \App\Models\SaleTransaction::create(['employee_id' => $cashier->employee_id,
            'transaction_date' => now(), 'subtotal' => 5, 'total_amount' => 5, 'payment_method' => 'cash', 'status' => 'completed']);
        app(\App\Services\InventoryOutboxService::class)->recordSaleEvent($sale->transaction_id,
            [['product_id' => $product->product_id, 'quantity' => 5]]);
        $this->assertEquals(95, app(SaleService::class)->getProductStockSnapshot()[$product->product_id]['stock']);
        $this->assertDatabaseHas('inventory_outbox_event', ['sale_transaction_id' => $sale->transaction_id, 'processed_at' => null, 'attempts' => 0]);
    }

    public function test_checkout_respects_reservations_against_remote_stock_even_when_local_stock_is_stale(): void
    {
        $product = $this->product();
        $this->remoteStock(10);
        $cashier = \App\Models\Employee::where('username', 'cashier')->firstOrFail();
        app(\App\Services\CardVerificationService::class)->submit([
            'payment_provider' => 'Visa', 'reference_number' => 'LIVE-RESERVED-001', 'amount_paid' => 10000,
            'items' => [['product_id' => $product->product_id, 'quantity' => 7]],
        ], $cashier->employee_id);
        $this->actingAs($cashier)->post(route('pos.store'), [
            'payment_method' => 'cash', 'amount_paid' => 10000,
            'items' => [['product_id' => $product->product_id, 'quantity' => 7]],
        ])->assertSessionHasErrors('items');
    }

    public function test_inventory_restock_can_be_sold_when_local_count_is_still_zero(): void
    {
        $product = $this->product();
        Inventory::where('product_id', $product->product_id)->update(['stock_quantity' => 0]);
        $this->remoteStock(10);
        $cashier = \App\Models\Employee::where('username', 'cashier')->firstOrFail();
        $this->actingAs($cashier)->post(route('pos.store'), [
            'payment_method' => 'cash', 'amount_paid' => 10000,
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
        ])->assertSessionHasNoErrors();
        $this->assertEquals(9, $product->fresh()->inventory->stock_quantity);
    }

    public function test_product_catalog_stock_feed_uses_inventory_availability(): void
    {
        $product = $this->product();
        $this->remoteStock(0);
        $manager = \App\Models\Employee::where('username', 'manager')->firstOrFail();
        $response = $this->actingAs($manager)->getJson(route('products.index'))->assertOk();
        $row = collect($response->json('stocks'))->firstWhere('id', $product->product_id);
        $this->assertNotNull($row);
        $this->assertEquals(0, $row['quantity']);
    }

    public function test_verifying_card_does_not_subtract_its_own_reservation_twice(): void
    {
        $product = $this->product();
        $this->remoteStock(10);
        $cashier = \App\Models\Employee::where('username', 'cashier')->firstOrFail();
        $manager = \App\Models\Employee::where('username', 'manager')->firstOrFail();
        $service = app(\App\Services\CardVerificationService::class);
        $pending = $service->submit([
            'payment_method' => 'card', 'payment_provider' => 'Visa', 'reference_number' => 'LIVE-VERIFY-001', 'amount_paid' => 10000,
            'items' => [['product_id' => $product->product_id, 'quantity' => 10]],
        ], $cashier->employee_id);
        $sale = $service->verify($pending, $manager->employee_id);
        $this->assertSame('completed', $sale->status);
        $this->assertEquals(0, $product->fresh()->inventory->stock_quantity);
    }

    public function test_acknowledgement_during_catalog_fetch_cannot_restore_already_sold_stock(): void
    {
        $product = $this->product();
        $cashier = \App\Models\Employee::where('username', 'cashier')->firstOrFail();
        $sale = \App\Models\SaleTransaction::create(['employee_id' => $cashier->employee_id,
            'transaction_date' => now(), 'subtotal' => 5, 'total_amount' => 5, 'payment_method' => 'cash', 'status' => 'completed']);
        app(\App\Services\InventoryOutboxService::class)->recordSaleEvent($sale->transaction_id,
            [['product_id' => $product->product_id, 'quantity' => 5]]);
        Http::fake(function () {
            DB::table('inventory_outbox_event')->update(['processed_at' => now(), 'status' => 'processed']);
            return Http::response(['data' => [['sku' => 'BEEF', 'external_id' => 'inventory:sku:BEEF',
                'status' => 'Active', 'on_hand' => 100]], 'meta' => ['page' => 1, 'last_page' => 1, 'total' => 1]]);
        });
        $this->assertEquals(95, app(SaleService::class)->getProductStockSnapshot()[$product->product_id]['stock']);
    }
}
