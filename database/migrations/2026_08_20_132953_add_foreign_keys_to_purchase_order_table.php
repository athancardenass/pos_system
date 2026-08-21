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
        Schema::table('purchase_order', function (Blueprint $table) {
            $table->foreign(['supplier_id'], 'purchase_order_ibfk_1')->references(['supplier_id'])->on('supplier')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['employee_id'], 'purchase_order_ibfk_2')->references(['employee_id'])->on('employee')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order', function (Blueprint $table) {
            $table->dropForeign('purchase_order_ibfk_1');
            $table->dropForeign('purchase_order_ibfk_2');
        });
    }
};
