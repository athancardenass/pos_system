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
        Schema::create('purchase_order_details', function (Blueprint $table) {
            $table->integer('purchase_detail_id', true);
            $table->integer('purchase_id')->index('purchase_order_purchase_id_index');
            $table->integer('product_id')->index('purchase_order_details_product_id_index');
            $table->integer('quantity');
            $table->decimal('unit_cost', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_details');
    }
};
