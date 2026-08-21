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
        Schema::create('product', function (Blueprint $table) {
            $table->integer('product_id', true);
            $table->integer('category_id')->nullable()->index('category_id');
            $table->integer('supplier_id')->nullable()->index('supplier_id');
            $table->string('product_name', 150)->index('idx_product_name');
            $table->string('description')->nullable();
            $table->string('barcode', 50)->unique('barcode');
            $table->decimal('unit_price', 10);
            $table->decimal('cost_price', 10);
            $table->integer('reorder_level')->default(10);

            $table->index(['barcode'], 'idx_product_barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
