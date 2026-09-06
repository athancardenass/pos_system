<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refund records: one sale_refund per refund event (full or partial),
     * with one sale_refund_item row per refunded line-item quantity.
     */
    public function up(): void
    {
        Schema::create('sale_refund', function (Blueprint $table) {
            $table->integer('refund_id', true);
            $table->integer('transaction_id')->index();
            $table->integer('employee_id')->nullable();
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->string('reason', 50);
            $table->string('notes', 255)->nullable();
            $table->boolean('is_full_refund')->default(false);
            $table->timestamp('refunded_at')->useCurrent();
            $table->foreign('transaction_id')->references('transaction_id')->on('sale_transaction');
            $table->foreign('employee_id')->references('employee_id')->on('employee');
        });

        Schema::create('sale_refund_item', function (Blueprint $table) {
            $table->integer('refund_item_id', true);
            $table->integer('refund_id');
            $table->integer('sale_detail_id');
            $table->integer('quantity');
            $table->decimal('amount', 12, 2)->default(0);
            $table->foreign('refund_id')->references('refund_id')->on('sale_refund')->cascadeOnDelete();
            $table->foreign('sale_detail_id')->references('sale_detail_id')->on('sale_details');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_refund_item');
        Schema::dropIfExists('sale_refund');
    }
};
