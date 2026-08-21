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
        Schema::create('purchase_order', function (Blueprint $table) {
            $table->integer('purchase_id', true);
            $table->integer('supplier_id')->index('supplier_id');
            $table->integer('employee_id')->index('employee_id');
            $table->date('order_date');
            $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order');
    }
};
