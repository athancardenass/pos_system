<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SaleTransaction;
use App\Services\RefundService;
use App\Services\SaleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->saleService = new SaleService();
    }

    public function test_get_pos_index_data_returns_all_expected_keys(): void
    {
        $data = $this->saleService->getPosIndexData();

        $this->assertArrayHasKey('products', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('customers', $data);
        $this->assertArrayHasKey('discounts', $data);
        $this->assertArrayHasKey('productsJson', $data);
        $this->assertArrayHasKey('customersJson', $data);

        $this->assertNotEmpty($data['products']);
        $this->assertNotEmpty($data['categories']);
    }

    public function test_load_sale_for_receipt_eager_loads_relations(): void
    {
        $employee = Employee::query()->firstOrFail();
        $sale = SaleTransaction::query()->create([
            'employee_id' => $employee->employee_id,
            'transaction_date' => now(),
            'subtotal' => 100.00,
            'total_amount' => 100.00,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $loaded = $this->saleService->loadSaleForReceipt($sale);

        $this->assertTrue($loaded->relationLoaded('employee'));
        $this->assertTrue($loaded->relationLoaded('saleDetails'));
        $this->assertTrue($loaded->relationLoaded('appliedPromotions'));
        $this->assertTrue($loaded->relationLoaded('couponRedemptions'));
    }

    public function test_refund_window_calculations(): void
    {
        $employee = Employee::query()->firstOrFail();

        // Fresh sale (today)
        $recentSale = SaleTransaction::query()->create([
            'employee_id' => $employee->employee_id,
            'transaction_date' => now(),
            'subtotal' => 100.00,
            'total_amount' => 100.00,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $this->assertFalse($this->saleService->isOutsideRefundWindow($recentSale));
        $this->assertEquals(RefundService::WINDOW_DAYS, $this->saleService->refundDaysRemaining($recentSale));

        // 10-day old sale (outside 7-day window)
        $oldSale = SaleTransaction::query()->create([
            'employee_id' => $employee->employee_id,
            'transaction_date' => now()->subDays(10),
            'subtotal' => 100.00,
            'total_amount' => 100.00,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $this->assertTrue($this->saleService->isOutsideRefundWindow($oldSale));
        $this->assertEquals(0, $this->saleService->refundDaysRemaining($oldSale));
    }
}
