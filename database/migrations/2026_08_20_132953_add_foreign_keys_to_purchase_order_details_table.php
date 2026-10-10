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
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->foreign(['purchase_id'], 'purchase_order_details_ibfk_1')->references(['purchase_id'])->on('purchase_order')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['product_id'], 'purchase_order_details_ibfk_2')->references(['product_id'])->on('product')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->dropForeign('purchase_order_details_ibfk_1');
            $table->dropForeign('purchase_order_details_ibfk_2');
        });
    }
};
