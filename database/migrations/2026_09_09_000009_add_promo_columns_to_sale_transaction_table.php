<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Promotion-engine discount columns on the sale header.
     *
     * subtotal / total_amount keep their meaning (gross line total -> final amount due).
     * These two columns only break out HOW MUCH of that drop came from the promotion
     * engine, so the receipt and reports can show it without re-deriving the math:
     *
     *   total_amount = subtotal - promo_discount - manual discount - coupon_discount
     *
     * The manual discount stays un-stored (exactly as before this change): it is the
     * remainder of that equation and is reconstructable from discount_id + the two
     * columns here (see SaleTransaction::manualDiscountAmount()).
     *
     * Nullable with default 0 so every pre-existing sale row keeps its old totals.
     */
    public function up(): void
    {
        Schema::table('sale_transaction', function (Blueprint $table) {
            $table->decimal('promo_discount', 10, 2)->nullable()->default(0)->after('total_amount');
            $table->decimal('coupon_discount', 10, 2)->nullable()->default(0)->after('promo_discount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_transaction', function (Blueprint $table) {
            $table->dropColumn(['promo_discount', 'coupon_discount']);
        });
    }
};
