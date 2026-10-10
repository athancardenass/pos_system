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
        Schema::table('sale_details', function (Blueprint $table) {
            $table->foreign(['transaction_id'], 'sale_details_ibfk_1')->references(['transaction_id'])->on('sale_transaction')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['product_id'], 'sale_details_ibfk_2')->references(['product_id'])->on('product')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropForeign('sale_details_ibfk_1');
            $table->dropForeign('sale_details_ibfk_2');
        });
    }
};
