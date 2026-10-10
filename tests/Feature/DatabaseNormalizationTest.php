<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\SaleTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_features_are_absent_and_payment_reads_sale_method(): void
    {
        $this->seed(DatabaseSeeder::class);
        foreach (['promotion', 'coupon', 'sale_promotion', 'coupon_redemption', 'purchase_order', 'purchase_order_details'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        $this->assertFalse(Schema::hasColumn('sale_transaction', 'promo_discount'));
        $this->assertFalse(Schema::hasColumn('sale_transaction', 'coupon_discount'));
        $this->assertFalse(Schema::hasColumn('payment', 'payment_method'));

        $sale = SaleTransaction::create([
            'employee_id' => Employee::firstOrFail()->employee_id,
            'transaction_date' => now(), 'subtotal' => 100, 'total_amount' => 100,
            'payment_method' => 'cash', 'status' => 'completed',
        ]);
        $payment = $sale->payment()->create(['amount_paid' => 100, 'change_amount' => 0, 'payment_date' => now()]);
        $this->assertSame('cash', $payment->fresh()->payment_method);
        $sale->update(['payment_method' => 'card']);
        $this->assertSame('card', $payment->fresh()->payment_method);
    }

    public function test_migration_refuses_to_discard_conflicting_payment_methods(): void
    {
        $this->seed(DatabaseSeeder::class);
        $migration = require database_path('migrations/2026_10_10_000001_normalize_payment_and_remove_promotions_and_coupons.php');
        $migration->down();
        $sale = SaleTransaction::create([
            'employee_id' => Employee::firstOrFail()->employee_id,
            'transaction_date' => now(), 'subtotal' => 100, 'total_amount' => 100,
            'payment_method' => 'cash', 'status' => 'completed',
        ]);
        DB::table('payment')->insert([
            'transaction_id' => $sale->transaction_id, 'payment_method' => 'card',
            'amount_paid' => 100, 'change_amount' => 0, 'payment_date' => now(),
        ]);
        try {
            $migration->up();
            $this->fail('Conflicting payment methods must stop normalization.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('conflicting', $exception->getMessage());
            $this->assertTrue(Schema::hasTable('promotion'));
            $this->assertTrue(Schema::hasColumn('payment', 'payment_method'));
        }
        DB::table('payment')->where('transaction_id', $sale->transaction_id)->update(['payment_method' => 'cash']);
        $migration->up();
        $this->assertFalse(Schema::hasColumn('payment', 'payment_method'));
        $this->assertDatabaseHas('payment', ['transaction_id' => $sale->transaction_id, 'amount_paid' => 100]);
    }
}
