<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudAndPosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_cashier_can_create_a_customer(): void
    {
        $this->actingAs($this->employee('cashier'))
            ->post(route('customers.store'), [
                'first_name' => 'Ana',
                'last_name' => 'Cruz',
                'contact_number' => '09171234567',
                'email' => 'ana@example.com',
                'address' => 'Manila',
                'date_of_birth' => '1990-01-01',
                'customer_status' => 'active',
            ])
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customer', [
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'email' => 'ana@example.com',
        ]);
    }

    public function test_cashier_cannot_manage_categories(): void
    {
        $this->actingAs($this->employee('cashier'))
            ->get(route('categories.index'))
            ->assertForbidden();
    }

    public function test_manager_can_create_category_and_product(): void
    {
        $manager = $this->employee('manager');

        $this->actingAs($manager)
            ->post(route('categories.store'), [
                'category_name' => 'Snacks',
                'description' => 'Chips and biscuits',
            ])
            ->assertRedirect(route('categories.index'));

        $category = Category::query()->firstOrFail();

        $this->actingAs($manager)
            ->post(route('products.store'), [
                'category_id' => $category->category_id,
                'product_name' => 'Potato Chips',
                'barcode' => 'SNACK-001',
                'unit_price' => 45.50,
                'cost_price' => 30,
                'reorder_level' => 5,
            ])
            ->assertRedirect(route('products.index'));

        $product = Product::query()->where('barcode', 'SNACK-001')->firstOrFail();

        $this->assertDatabaseHas('inventory', [
            'product_id' => $product->product_id,
            'stock_quantity' => 0,
        ]);
    }

    public function test_admin_can_view_employees_and_cashier_cannot(): void
    {
        $this->actingAs($this->employee('manager'))
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('manager');

        $this->actingAs($this->employee('cashier'))
            ->get(route('employees.index'))
            ->assertForbidden();
    }

    public function test_pos_completes_a_sale_and_reduces_stock(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Bottled Water',
            'barcode' => 'WATER-001',
            'unit_price' => 20,
            'cost_price' => 10,
            'reorder_level' => 5,
        ]);

        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 10,
        ]);

        $customer = Customer::query()->create([
            'first_name' => 'Ben',
            'last_name' => 'Santos',
            'customer_status' => 'active',
        ]);

        $this->actingAs($this->employee('cashier'))
            ->post(route('pos.store'), [
                'customer_id' => $customer->customer_id,
                'payment_method' => 'cash',
                'amount_paid' => 50,
                'items' => [
                    ['product_id' => $product->product_id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sale_transaction', [
            'customer_id' => $customer->customer_id,
            'total_amount' => 40,
        ]);

        $this->assertEquals(8.000, $product->fresh()->inventory->stock_quantity);
    }

    public function test_cashier_cannot_access_dashboard_and_redirects_to_pos(): void
    {
        $cashier = $this->employee('cashier');

        // Cashier cannot access /dashboard
        $this->actingAs($cashier)
            ->get(route('dashboard'))
            ->assertStatus(403);

        // Cashier accessing root / redirects to /pos
        $this->actingAs($cashier)
            ->get('/')
            ->assertRedirect(route('pos.index'));

        // Manager can access /dashboard
        $manager = $this->employee('manager');
        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertStatus(200);

        // Manager accessing root / redirects to /dashboard
        $this->actingAs($manager)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_manager_reports_access_and_cashier_forbidden(): void
    {
        $cashier = $this->employee('cashier');
        $this->actingAs($cashier)
            ->get(route('reports.index'))
            ->assertStatus(403);

        $manager = $this->employee('manager');
        $this->actingAs($manager)
            ->get(route('reports.index'))
            ->assertStatus(200)
            ->assertSee('Daily')
            ->assertSee('Weekly')
            ->assertSee('Monthly')
            ->assertSee('Yearly')
            ->assertSee('Apply');
    }

    private function employee(string $username): Employee
    {
        return Employee::query()->where('username', $username)->firstOrFail();
    }
}
