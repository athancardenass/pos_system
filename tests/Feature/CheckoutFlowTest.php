<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SaleTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function employee(string $username): Employee
    {
        return Employee::query()->where('username', $username)->firstOrFail();
    }

    public function test_checkout_creates_sale_with_correct_totals(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Test Item',
            'barcode' => '4006381000997',
            'unit_price' => 100.00,
            'cost_price' => 50.00,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 10,
        ]);

        $customer = Customer::query()->create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'customer_status' => 'active',
        ]);

        $response = $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'customer_id' => $customer->customer_id,
                'payment_method' => 'cash',
                'amount_paid' => 250.00,
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 2],
                ],
            ]);

        $response->assertRedirect();

        $sale = SaleTransaction::query()->latest('transaction_id')->first();
        $this->assertNotNull($sale);
        $this->assertEquals(200.00, $sale->subtotal);
        $this->assertEquals(200.00, $sale->total_amount);
        $this->assertEquals('cash', $sale->payment_method);
        $this->assertEquals('completed', $sale->status);
        $this->assertEquals($customer->customer_id, $sale->customer_id);
    }

    public function test_checkout_deducts_inventory(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Test Item',
            'barcode' => '4006381000996',
            'unit_price' => 50.00,
            'cost_price' => 25.00,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 10,
        ]);

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 200.00,
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 3],
                ],
            ]);

        $this->assertEquals(7, $product->fresh()->inventory->stock_quantity);
    }

    public function test_checkout_creates_payment_record(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Test Item',
            'barcode' => '4006381000995',
            'unit_price' => 75.00,
            'cost_price' => 35.00,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 10,
        ]);

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'payment_method' => 'card',
                'amount_paid' => 100.00,
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 1],
                ],
            ]);

        $sale = SaleTransaction::query()->latest('transaction_id')->first();
        $this->assertNotNull($sale->payment);
        $this->assertEquals(100.00, $sale->payment->amount_paid);
        $this->assertEquals(25.00, $sale->payment->change_amount);
        $this->assertEquals('card', $sale->payment->payment_method);
    }

    public function test_checkout_creates_receipt(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Test Item',
            'barcode' => '4006381000994',
            'unit_price' => 30.00,
            'cost_price' => 15.00,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 10,
        ]);

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 50.00,
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 1],
                ],
            ]);

        $sale = SaleTransaction::query()->latest('transaction_id')->first();
        $this->assertNotNull($sale->receipt);
        $this->assertStringStartsWith('R', $sale->receipt->receipt_number);
    }

    public function test_checkout_applies_discount_correctly(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Test Item',
            'barcode' => '4006381000993',
            'unit_price' => 100.00,
            'cost_price' => 50.00,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 10,
        ]);

        $discount = Discount::query()->create([
            'discount_name' => '10% Off',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'discount_id' => $discount->discount_id,
                'payment_method' => 'cash',
                'amount_paid' => 100.00,
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 1],
                ],
            ]);

        $sale = SaleTransaction::query()->latest('transaction_id')->first();
        $this->assertEquals(100.00, $sale->subtotal);
        $this->assertEquals(90.00, $sale->total_amount);
    }

    public function test_checkout_rejects_insufficient_stock(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Test Item',
            'barcode' => '4006381000992',
            'unit_price' => 10.00,
            'cost_price' => 5.00,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 2,
        ]);

        $beforeCount = SaleTransaction::query()->count();

        $response = $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 1000.00,
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 5],
                ],
            ]);

        $response->assertSessionHasErrors('items');
        $this->assertEquals($beforeCount, SaleTransaction::query()->count());
        $this->assertEquals(2, $product->fresh()->inventory->stock_quantity);
    }

    public function test_checkout_rejects_duplicate_product_lines_oversell(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Test Item',
            'barcode' => '4006381000991',
            'unit_price' => 10.00,
            'cost_price' => 5.00,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 5,
        ]);

        $beforeCount = SaleTransaction::query()->count();

        $response = $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 1000.00,
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 3],
                    ['product_id' => $product->product_id, 'quantity' => 3],
                ],
            ]);

        $response->assertSessionHasErrors('items');
        $this->assertEquals($beforeCount, SaleTransaction::query()->count());
        $this->assertEquals(5, $product->fresh()->inventory->stock_quantity);
    }

    public function test_checkout_rejects_insufficient_payment(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Test Item',
            'barcode' => '4006381000990',
            'unit_price' => 100.00,
            'cost_price' => 50.00,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 10,
        ]);

        $beforeCount = SaleTransaction::query()->count();

        $response = $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 50.00,
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 1],
                ],
            ]);

        $response->assertSessionHasErrors('amount_paid');
        $this->assertEquals($beforeCount, SaleTransaction::query()->count());
    }

    public function test_checkout_requires_at_least_one_item(): void
    {
        $beforeCount = SaleTransaction::query()->count();

        $response = $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 100.00,
                'items' => [],
            ]);

        $response->assertSessionHasErrors('items');
        $this->assertEquals($beforeCount, SaleTransaction::query()->count());
    }

    public function test_checkout_updates_customer_loyalty_points(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Test Item',
            'barcode' => '4006381000989',
            'unit_price' => 150.00,
            'cost_price' => 75.00,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 10,
        ]);

        $customer = Customer::query()->create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'customer_status' => 'active',
            'total_purchases' => 0,
            'loyalty_points' => 0,
        ]);

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'customer_id' => $customer->customer_id,
                'payment_method' => 'cash',
                'amount_paid' => 200.00,
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 1],
                ],
            ]);

        $customer->refresh();
        $this->assertEquals(150.00, $customer->total_purchases);
        $this->assertEquals(1, $customer->loyalty_points);
    }

    public function test_checkout_works_for_walk_in_customer(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Test Item',
            'barcode' => '4006381000988',
            'unit_price' => 25.00,
            'cost_price' => 12.00,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 10,
        ]);

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'payment_method' => 'e-wallet',
                'amount_paid' => 50.00,
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        $sale = SaleTransaction::query()->latest('transaction_id')->first();
        $this->assertNull($sale->customer_id);
        $this->assertEquals(50.00, $sale->total_amount);
    }

    public function test_checkout_creates_sale_details_lines(): void
    {
        $product1 = Product::query()->create([
            'product_name' => 'Item A',
            'barcode' => '4006381000987',
            'unit_price' => 50.00,
            'cost_price' => 25.00,
            'reorder_level' => 5,
        ]);
        $product2 = Product::query()->create([
            'product_name' => 'Item B',
            'barcode' => '4006381000986',
            'unit_price' => 30.00,
            'cost_price' => 15.00,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create([
            'product_id' => $product1->product_id,
            'stock_quantity' => 10,
        ]);
        Inventory::query()->create([
            'product_id' => $product2->product_id,
            'stock_quantity' => 10,
        ]);

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 200.00,
                'items' => [
                    ['product_id' => $product1->product_id, 'quantity' => 1],
                    ['product_id' => $product2->product_id, 'quantity' => 2],
                ],
            ]);

        $sale = SaleTransaction::query()->where('subtotal', 110.00)->latest('transaction_id')->first();
        $sale->load('saleDetails');
        $this->assertCount(2, $sale->saleDetails);
        $this->assertEquals(110.00, $sale->subtotal);
    }
}
