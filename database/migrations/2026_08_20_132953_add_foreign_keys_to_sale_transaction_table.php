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
        Schema::table('sale_transaction', function (Blueprint $table) {
            $table->foreign(['customer_id'], 'sale_transaction_ibfk_1')->references(['customer_id'])->on('customer')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['employee_id'], 'sale_transaction_ibfk_2')->references(['employee_id'])->on('employee')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['discount_id'], 'sale_transaction_ibfk_3')->references(['discount_id'])->on('discount')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_transaction', function (Blueprint $table) {
            $table->dropForeign('sale_transaction_ibfk_1');
            $table->dropForeign('sale_transaction_ibfk_2');
            $table->dropForeign('sale_transaction_ibfk_3');
        });
    }
};
