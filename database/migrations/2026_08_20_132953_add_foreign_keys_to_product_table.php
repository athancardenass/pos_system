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
        Schema::table('product', function (Blueprint $table) {
            $table->foreign(['category_id'], 'product_ibfk_1')->references(['category_id'])->on('category')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['supplier_id'], 'product_ibfk_2')->references(['supplier_id'])->on('supplier')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropForeign('product_ibfk_1');
            $table->dropForeign('product_ibfk_2');
        });
    }
};
