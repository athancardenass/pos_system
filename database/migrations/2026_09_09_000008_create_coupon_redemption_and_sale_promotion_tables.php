<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Promotion engine audit tables.
     *
     * coupon_redemption = one row per coupon consumed on a sale (usage counter source of truth)
     * sale_promotion    = one row per auto-promotion applied to a sale, with a rule snapshot
     *
     * FKs use ON DELETE RESTRICT: sales are never deleted in this system, so a redemption or
     * a promotion that has been spent must not be silently orphaned by a delete.
     */
    public function up(): void
    {
        if (! Schema::hasTable('coupon_redemption')) {
            Schema::create('coupon_redemption', function (Blueprint $table) {
                $table->integer('redemption_id', true);
                $table->integer('coupon_id')->index('coupon_redemption_coupon_id_index');
                $table->integer('transaction_id')->index('coupon_redemption_transaction_id_index');
                // Null = walk-in (skips the per-customer limit, still counts toward max_uses).
                $table->integer('customer_id')->nullable()->index('coupon_redemption_customer_id_index');
                $table->dateTime('redeemed_at')->useCurrent();
                $table->decimal('amount_applied', 10, 2)->default(0);

                $table->foreign('coupon_id')->references('coupon_id')->on('coupon')->onDelete('restrict');
                $table->foreign('transaction_id')->references('transaction_id')->on('sale_transaction')->onDelete('restrict');
                $table->foreign('customer_id')->references('customer_id')->on('customer')->onDelete('restrict');
            });
        }

        if (! Schema::hasTable('sale_promotion')) {
            Schema::create('sale_promotion', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('transaction_id')->index('sale_promotion_transaction_id_index');
                $table->integer('promotion_id')->index('sale_promotion_promotion_id_index');
                $table->decimal('amount_discounted', 10, 2)->default(0);
                // Rule details at time of sale (promotion rows may be edited later).
                $table->json('snapshot')->nullable();

                $table->foreign('transaction_id')->references('transaction_id')->on('sale_transaction')->onDelete('restrict');
                $table->foreign('promotion_id')->references('promotion_id')->on('promotion')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_promotion');
        Schema::dropIfExists('coupon_redemption');
    }
};
