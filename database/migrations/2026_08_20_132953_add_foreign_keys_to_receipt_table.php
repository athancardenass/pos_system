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
        Schema::table('receipt', function (Blueprint $table) {
            $table->foreign(['transaction_id'], 'receipt_ibfk_1')->references(['transaction_id'])->on('sale_transaction')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipt', function (Blueprint $table) {
            $table->dropForeign('receipt_ibfk_1');
        });
    }
};
