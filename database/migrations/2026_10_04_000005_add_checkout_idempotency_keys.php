<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_transaction', function (Blueprint $table): void {
            $table->uuid('checkout_idempotency_key')->nullable();
            $table->unique('checkout_idempotency_key', 'sale_checkout_idempotency_unique');
        });

        Schema::table('pending_ewallet_verifications', function (Blueprint $table): void {
            $table->uuid('checkout_idempotency_key')->nullable();
            $table->unique('checkout_idempotency_key', 'pending_ewallet_checkout_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pending_ewallet_verifications', function (Blueprint $table): void {
            $table->dropUnique('pending_ewallet_checkout_idempotency_unique');
            $table->dropColumn('checkout_idempotency_key');
        });

        Schema::table('sale_transaction', function (Blueprint $table): void {
            $table->dropUnique('sale_checkout_idempotency_unique');
            $table->dropColumn('checkout_idempotency_key');
        });
    }
};
