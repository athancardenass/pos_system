<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sale_transaction', function (Blueprint $table) {
            $table->integer('transaction_id', true);
            $table->integer('customer_id')->nullable()->index('customer_id');
            $table->integer('employee_id')->index('idx_transaction_employee');
            $table->integer('discount_id')->nullable()->index('discount_id');
            $table->dateTime('transaction_date')->useCurrent()->index('idx_transaction_date');
            $table->decimal('subtotal', 10);
            $table->decimal('total_amount', 10);
            $table->string('payment_method', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_transaction');
    }
};
