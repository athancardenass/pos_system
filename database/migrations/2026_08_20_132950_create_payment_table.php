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
        Schema::create('payment', function (Blueprint $table) {
            $table->integer('payment_id', true);
            $table->integer('transaction_id')->unique('transaction_id');
            $table->string('payment_method', 50);
            $table->decimal('amount_paid', 10);
            $table->decimal('change_amount', 10)->default(0);
            $table->dateTime('payment_date')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
