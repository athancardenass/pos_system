<?php

namespace Tests\Feature\Integration;

use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\Integration\MasterSyncService;
use App\Services\Integration\Inventory\StockEventDispatcher;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MasterSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['services.inventory.base_url' => 'https://inventory.test', 'services.inventory.token' => 'test-token',
            'services.hrms.base_url' => 'https://hr.test', 'services.hrms.token' => 'hr-token', 'services.pos_instance_id' => 'pos-test']);
        Http::preventStrayRequests();
    }

    private function catalog(float $stock = 10): array
    {
        return ['data' => [[
            'external_id' => 'inventory:sku:SKU-1', 'sku' => 'SKU-1', 'name' => 'Rice', 'barcode' => '4006381333931',
            'unit_price' => 50, 'cost_price' => 30, 'reorder_point' => 2, 'status' => 'Active', 'on_hand' => $stock,
        ]], 'meta' => ['page' => 1, 'last_page' => 1, 'total' => 1]];
    }

    public function test_catalog_retries_preserve_identity_and_pending_stock_changes(): void
    {
        Http::fake(['https://inventory.test/api/v1/catalog/products*' => Http::response($this->catalog())]);
        $sync = app(MasterSyncService::class);
        $sync->sync('inventory');
        $sync->sync('inventory');
        $this->assertSame(1, Product::count());
        $product = Product::firstOrFail();
        $this->assertSame('SKU-1', $product->inventoryMapping->inventory_sku);
        $this->assertEquals(10, $product->inventory->stock_quantity);
        Inventory::where('product_id', $product->product_id)->update(['stock_quantity' => 8]);
        $employee = Employee::create(['role_id' => 1, 'first_name' => 'Test', 'last_name' => 'Cashier', 'username' => 'test', 'password' => 'test', 'hire_date' => now(), 'status' => 'active']);
        $saleId = DB::table('sale_transaction')->insertGetId(['employee_id' => $employee->employee_id, 'subtotal' => 100, 'total_amount' => 100, 'payment_method' => 'cash', 'status' => 'completed', 'transaction_date' => now()]);
        DB::table('inventory_outbox_event')->insert([
            'event_type' => 'sale.completed', 'sale_transaction_id' => $saleId, 'product_id' => $product->product_id,
            'inventory_sku' => 'SKU-1', 'quantity' => -2, 'payload' => '{}', 'status' => 'pending', 'attempts' => 0, 'available_at' => now(), 'created_at' => now(),
        ]);
        $sync->sync('inventory');
        $this->assertEquals(8, $product->fresh()->inventory->stock_quantity);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake([
            'https://inventory.test/api/v1/pos/stock-events' => Http::response(['accepted' => true, 'duplicate' => false]),
            'https://inventory.test/api/v1/catalog/products*' => Http::response($this->catalog(8)),
        ]);
        // The prompt worker sends sales even without a new Inventory webhook.
        $this->assertSame(0, app(\App\Services\Integration\Inventory\WebhookInbox::class)->process($sync, app(StockEventDispatcher::class)));
        $this->assertEquals(8, $product->fresh()->inventory->stock_quantity);
        $this->assertSame(0, app(StockEventDispatcher::class)->dispatch());
        Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['sku'] === 'SKU-1' && $request['event_key'] === 'pos-test:1' && $request['quantity_delta'] === -2.0);
    }

    public function test_hr_retries_preserve_credentials_and_do_not_grant_manager_access(): void
    {
        Http::fake(['https://hr.test/api/v1/employees*' => Http::response([
            'data' => [['external_id' => 'hrms:employee:EMP-1', 'employee_no' => 'EMP-1', 'first_name' => 'Ana', 'last_name' => 'Santos',
                'contact_number' => null, 'hire_date' => '2025-01-01', 'position' => 'Manager', 'employment_status' => 'active']],
            'meta' => ['page' => 1, 'last_page' => 1, 'total' => 1],
        ])]);
        $sync = app(MasterSyncService::class);
        $sync->sync('hrms');
        $employee = Employee::firstOrFail();
        $password = $employee->getRawOriginal('password');
        $sync->sync('hrms');
        $this->assertSame(1, Employee::count());
        $this->assertSame($password, $employee->fresh()->getRawOriginal('password'));
        $this->assertSame('inactive', $employee->fresh()->status);
        $this->assertTrue($employee->hasRole('Cashier'));
    }

    public function test_pos_specific_hr_feed_accepts_count_envelope_and_refuses_incomplete_snapshots(): void
    {
        config(['services.hrms.employees_path' => '/api/pos/employees']);
        Http::fake(['https://hr.test/api/pos/employees*' => Http::sequence()
            ->push(['data' => [], 'count' => 0, 'generated_at' => '2026-10-10T19:00:00Z'])
            ->push(['data' => [], 'count' => 1, 'generated_at' => '2026-10-10T19:00:00Z'])]);
        $this->assertSame(0, app(MasterSyncService::class)->sync('hrms'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid or incomplete HRMS employee snapshot.');
        app(MasterSyncService::class)->sync('hrms');
    }

    public function test_team_hr_feed_maps_mobile_status_and_stable_employee_number_without_changing_local_access(): void
    {
        config(['services.hrms.employees_path' => '/api/pos/employees']);
        Http::fake(['https://hr.test/api/pos/employees*' => Http::response([
            'data' => [['employee_no' => 'EMP-NEW', 'first_name' => 'Ana', 'last_name' => 'Santos',
                'full_name' => 'Ana Santos', 'mobile' => '09170000001', 'email' => 'ana@example.test',
                'department' => 'Point of Sale', 'position' => 'Staff', 'status' => 'active', 'hire_date' => '2026-10-10']],
            'count' => 1, 'generated_at' => '2026-10-10T19:00:00+08:00',
        ])]);
        $sync = app(MasterSyncService::class);
        $this->assertSame(1, $sync->sync('hrms'));
        $employee = Employee::where('hrms_employee_no', 'EMP-NEW')->firstOrFail();
        $password = $employee->getRawOriginal('password');
        $this->assertSame('09170000001', $employee->contact_number);
        $this->assertSame('active', $employee->hrms_employment_status);
        $this->assertSame('inactive', $employee->status);
        $this->assertSame(1, $sync->sync('hrms'));
        $this->assertSame($password, $employee->fresh()->getRawOriginal('password'));
        $this->assertSame(1, Employee::where('hrms_employee_no', 'EMP-NEW')->count());
        $lookup = app(\App\Services\Integration\HR\EmployeeService::class)->lookupByEmployeeNo('EMP-NEW');
        $this->assertSame(200, $lookup['status']);
        $this->assertSame('hrms:employee:EMP-NEW', $lookup['employee']['external_id']);
    }
}
