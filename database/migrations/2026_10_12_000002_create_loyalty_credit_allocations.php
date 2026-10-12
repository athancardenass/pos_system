<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_credit_allocations', function (Blueprint $t) {
            $t->id();
            $t->integer('sale_id');
            $t->integer('origin_sale_id');
            $t->decimal('remaining_amount', 12, 2);
            $t->foreign('sale_id')->references('transaction_id')->on('sale_transaction');
            $t->foreign('origin_sale_id')->references('transaction_id')->on('sale_transaction');
            $t->unique(['sale_id', 'origin_sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_credit_allocations');
    }
};
