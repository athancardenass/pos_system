<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Receipt;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_cashier_can_find_another_cashiers_original_receipt_for_return(): void
    {
        $cashier = Employee::where('username', 'cashier')->firstOrFail();
        $receipt = Receipt::whereHas('saleTransaction', fn ($q) => $q->where('employee_id', '!=', $cashier->employee_id))->firstOrFail();
        $this->actingAs($cashier)->get('/pos/receipts/search?q='.urlencode($receipt->receipt_number))->assertOk()->assertSee($receipt->receipt_number)->assertSee(route('pos.show', $receipt->transaction_id), false);
    }

    public function test_unknown_receipt_is_reported_without_returning_an_unrelated_sale(): void
    {
        $cashier = Employee::where('username', 'cashier')->firstOrFail();
        $this->actingAs($cashier)->get('/pos/receipts/search?q=DOES-NOT-EXIST')->assertOk()->assertSee('No receipt found');
    }
}
