<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Receipt;
use App\Models\SaleTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_sales_report_lists_all_completed_sales_with_latest_first_and_excludes_refunded_sales(): void
    {
        $manager = Employee::query()->where('username', 'manager')->firstOrFail();
        $date = Carbon::create(2040, 1, 1)->startOfDay();

        $refunded = SaleTransaction::create([
            'employee_id' => $manager->employee_id,
            'transaction_date' => $date->copy()->addMinute(),
            'subtotal' => 25,
            'total_amount' => 25,
            'payment_method' => 'cash',
            'status' => 'refunded',
        ]);

        $completed = [];
        for ($minute = 2; $minute <= 17; $minute++) {
            $completed[] = SaleTransaction::create([
                'employee_id' => $manager->employee_id,
                'transaction_date' => $date->copy()->addMinutes($minute),
                'subtotal' => 25,
                'total_amount' => 25,
                'payment_method' => 'cash',
                'status' => 'completed',
            ]);
        }

        $receipt = Receipt::create([
            'transaction_id' => $completed[15]->transaction_id,
            'receipt_number' => 'POS-2040-000016',
            'issued_date' => $completed[15]->transaction_date,
        ]);

        $filters = ['from' => $date->toDateString(), 'to' => $date->toDateString()];
        $response = $this->actingAs($manager)
            ->get(route('reports.index', $filters));
        $response
            ->assertOk()
            ->assertSee('<th>Receipt No.</th>', false)
            ->assertSee('class="report-receipt-link"', false)
            ->assertSee(route('pos.show', $completed[15]->transaction_id), false)
            ->assertSee($receipt->receipt_number)
            ->assertSee((string) $completed[15]->transaction_id)
            ->assertSee('<nav class="pagination"', false)
            ->assertDontSee('Pagination Navigation')
            ->assertDontSee('&lsaquo;', false)
            ->assertDontSee('&rsaquo;', false)
            ->assertDontSee('<td>'.$refunded->transaction_id.'</td>', false);

        $this->get(route('reports.index', [...$filters, 'page' => 2]))
            ->assertOk()
            ->assertSee((string) $completed[0]->transaction_id);

        $export = $this->get(route('reports.export', ['type' => 'sales', ...$filters]))
            ->assertOk();
        $csv = $export->streamedContent();
        $this->assertStringContainsString('Receipt No.', $csv);
        $this->assertStringContainsString($receipt->receipt_number, $csv);
        $this->assertStringContainsString('Completed', $csv);
        $this->assertStringNotContainsString("\n{$refunded->transaction_id},", $csv);
    }

    public function test_default_sales_report_covers_history_older_than_thirty_days(): void
    {
        $manager = Employee::query()->where('username', 'manager')->firstOrFail();
        $oldDate = Carbon::create(2001, 1, 1)->startOfDay();
        $oldSale = SaleTransaction::create([
            'employee_id' => $manager->employee_id,
            'transaction_date' => $oldDate,
            'subtotal' => 25,
            'total_amount' => 25,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);
        $totalPages = (int) ceil(SaleTransaction::query()->where('status', 'completed')->count() / 15);

        $this->actingAs($manager)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('name="from" value="'.$oldDate->toDateString().'"', false);

        $this->get(route('reports.index', ['page' => $totalPages]))
            ->assertOk()
            ->assertSee((string) $oldSale->transaction_id);
    }
}
