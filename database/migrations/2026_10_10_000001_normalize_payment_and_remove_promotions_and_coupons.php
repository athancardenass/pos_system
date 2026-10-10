<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check before destructive DDL: never silently discard conflicting methods.
        if (Schema::hasColumn('payment', 'payment_method')) {
            $conflicts = DB::table('payment as p')
                ->leftJoin('sale_transaction as s', 's.transaction_id', '=', 'p.transaction_id')
                ->where(function ($query) {
                    $query->whereNull('s.transaction_id')
                        ->orWhereColumn('p.payment_method', '<>', 's.payment_method');
                })->exists();

            if ($conflicts) {
                throw new RuntimeException('Resolve orphan payments or conflicting sale/payment methods before normalization.');
            }

            Schema::table('payment', fn (Blueprint $table) => $table->dropColumn('payment_method'));
        }

        Schema::dropIfExists('sale_promotion');
        Schema::dropIfExists('coupon_redemption');
        Schema::dropIfExists('promotion');
        Schema::dropIfExists('coupon');

        foreach (['promo_discount', 'coupon_discount'] as $column) {
            if (Schema::hasColumn('sale_transaction', $column)) {
                Schema::table('sale_transaction', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payment', 'payment_method')) {
            Schema::table('payment', fn (Blueprint $table) => $table->string('payment_method', 50)->default('cash'));
            DB::table('payment')->orderBy('payment_id')->each(function ($payment) {
                $method = DB::table('sale_transaction')->where('transaction_id', $payment->transaction_id)->value('payment_method');
                DB::table('payment')->where('payment_id', $payment->payment_id)->update(['payment_method' => $method ?? 'cash']);
            });
        }

        // Rollback restores structure; removed feature data requires the backup.
        foreach ([
            '2026_09_09_000007_create_promotion_and_coupon_tables.php',
            '2026_09_09_000008_create_coupon_redemption_and_sale_promotion_tables.php',
            '2026_09_09_000009_add_promo_columns_to_sale_transaction_table.php',
        ] as $file) {
            (require __DIR__.'/'.$file)->up();
        }
    }
};
