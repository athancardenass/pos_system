<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SaleRefund;
use App\Models\SaleTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RefundFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @param array<int, float|string> $items */
    private function authorizeRefund(Employee $cashier, Employee $manager, SaleTransaction $sale, array $items): string
    {
        $manager->manager_pin_hash = Hash::make('2468');
        $manager->save();

        $response = $this->actingAs($cashier)->postJson(route('manager-authorization.authorize'), [
            'action' => 'sale_refund',
            'pin' => '2468',
            'register_id' => 'REG 01',
            'details' => ['sale_id' => $sale->transaction_id, 'items' => $items],
            'reason' => 'damaged',
        ]);
        $response->assertOk();

        return (string) $response->json('token');
    }

    public function test_refund_records_customer_reference_event_without_local_customer_row(): void
    {
        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();
        $manager = Employee::query()->where('username', 'manager')->firstOrFail();
        $product = Product::query()->create([
            'product_name' => 'Reference-only refund item',
            'barcode' => Product::generateBarcode(),
            'unit_price' => 40.00,
            'cost_price' => 20.00,
            'reorder_level' => 1,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 5,
        ]);

        $this->actingAs($cashier)
            ->post(route('pos.store'), [
                'customer_id' => 987654,
                'payment_method' => 'cash',
                'amount_paid' => '40.00',
                'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            ])
            ->assertRedirect();

        $sale = SaleTransaction::query()->latest('transaction_id')->firstOrFail();
        $detail = $sale->saleDetails()->firstOrFail();
        $token = $this->authorizeRefund($cashier, $manager, $sale, [$detail->sale_detail_id => 1]);

        $this->actingAs($cashier)
            ->post(route('pos.refund', $sale), [
                'manager_authorization_token' => $token,
                'register_id' => 'REG 01',
                'reason' => 'damaged',
                'items' => [$detail->sale_detail_id => 1],
            ])
            ->assertRedirect(route('pos.show', $sale));

        $this->assertDatabaseHas('customer_outbox_event', [
            'event_type' => 'sale.refunded',
            'sale_transaction_id' => $sale->transaction_id,
            'customer_id' => 987654,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('customer', ['customer_id' => 987654]);
    }

    public function test_refund_writes_inventory_event_for_restocked_quantities(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Inventory outbox refund item',
            'barcode' => Product::generateBarcode(),
            'unit_price' => 40.00,
            'cost_price' => 20.00,
            'reorder_level' => 1,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 5,
        ]);

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();
        $this->actingAs($cashier)
            ->post(route('pos.store'), [
                'payment_method' => 'cash',
                'amount_paid' => '40.00',
                'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            ])
            ->assertRedirect();

        $sale = SaleTransaction::query()->latest('transaction_id')->firstOrFail();
        $detail = $sale->saleDetails()->firstOrFail();
        $manager = Employee::query()->where('username', 'manager')->firstOrFail();
        $token = $this->authorizeRefund($cashier, $manager, $sale, [$detail->sale_detail_id => 1]);

        $this->actingAs($cashier)
            ->post(route('pos.refund', $sale), [
                'manager_authorization_token' => $token,
                'register_id' => 'REG 01',
                'reason' => 'damaged',
                'items' => [$detail->sale_detail_id => 1],
            ])
            ->assertRedirect(route('pos.show', $sale));

        $refund = $sale->refunds()->firstOrFail();
        $this->assertDatabaseHas('inventory_outbox_event', [
            'event_type' => 'sale.refunded',
            'sale_transaction_id' => $sale->transaction_id,
            'refund_id' => $refund->refund_id,
            'product_id' => $product->product_id,
            'quantity' => '1.000',
            'status' => 'pending',
        ]);
    }

    public function test_fractional_partial_refunds_pro_rate_amount_and_restore_fractional_stock(): void
    {
        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();
        $manager = Employee::query()->where('username', 'manager')->firstOrFail();
        $product = Product::query()->create([
            'product_name' => 'Bulk Rice',
            'barcode' => Product::generateBarcode(),
            'unit_price' => 100.00,
            'cost_price' => 60.00,
            'reorder_level' => 1,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 5.000,
        ]);

        $this->actingAs($cashier)
            ->post(route('pos.store'), [
                'payment_method' => 'cash',
                'amount_paid' => '150.00',
                'items' => [[
                    'product_id' => $product->product_id,
                    'quantity' => '1.500',
                ]],
            ])
            ->assertRedirect();

        $sale = SaleTransaction::query()->latest('transaction_id')->firstOrFail();
        $detail = $sale->saleDetails()->firstOrFail();
        $this->assertEquals(3.5, (float) $product->fresh()->inventory->stock_quantity);

        $firstRefundToken = $this->authorizeRefund($cashier, $manager, $sale, [$detail->sale_detail_id => '0.500']);
        $this->actingAs($cashier)
            ->post(route('pos.refund', $sale), [
                'manager_authorization_token' => $firstRefundToken,
                'register_id' => 'REG 01',
                'reason' => 'damaged',
                'items' => [$detail->sale_detail_id => '0.500'],
            ])
            ->assertRedirect(route('pos.show', $sale));

        $refund = $sale->refunds()->with('items')->firstOrFail();
        $refundItem = $refund->items->firstOrFail();

        $this->assertSame('0.500', $refundItem->quantity);
        $this->assertEquals(50.00, (float) $refundItem->amount);
        $this->assertEquals(50.00, (float) $refund->refund_amount);
        $this->assertEquals(4.0, (float) $product->fresh()->inventory->stock_quantity);
        $this->assertEquals(0.5, $detail->fresh()->refundedQuantity());
        $this->assertEquals(1.0, $detail->fresh()->refundableQuantity());
        $this->assertSame('completed', $sale->fresh()->status);

        $this->actingAs($cashier)
            ->get(route('pos.show', $sale))
            ->assertOk()
            ->assertSee('step="0.001"', false);

        $invalidRefundToken = $this->authorizeRefund($cashier, $manager, $sale, [$detail->sale_detail_id => '1.001']);
        $this->actingAs($cashier)
            ->post(route('pos.refund', $sale), [
                'manager_authorization_token' => $invalidRefundToken,
                'register_id' => 'REG 01',
                'reason' => 'damaged',
                'items' => [$detail->sale_detail_id => '1.001'],
            ])
            ->assertSessionHas('error');

        $this->assertEquals(4.0, (float) $product->fresh()->inventory->stock_quantity);
        $this->assertCount(1, $sale->refunds()->get());

        $secondRefundToken = $this->authorizeRefund($cashier, $manager, $sale, [$detail->sale_detail_id => '1.000']);
        $this->actingAs($cashier)
            ->post(route('pos.refund', $sale), [
                'manager_authorization_token' => $secondRefundToken,
                'register_id' => 'REG 01',
                'reason' => 'damaged',
                'items' => [$detail->sale_detail_id => '1.000'],
            ])
            ->assertRedirect(route('pos.show', $sale));

        $this->assertSame('refunded', $sale->fresh()->status);
        $this->assertEquals(5.0, (float) $product->fresh()->inventory->stock_quantity);
        $this->assertEquals(150.00, (float) $sale->refunds()->sum('refund_amount'));
        $this->assertEquals(1.5, $detail->fresh()->refundedQuantity());
        $this->assertEquals(0.0, $detail->fresh()->refundableQuantity());

        $lastRefundItem = SaleRefund::query()->latest('refund_id')->firstOrFail()->items()->firstOrFail();
        $this->assertSame('1.000', $lastRefundItem->quantity);
    }
}
