<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_outbox_event', function (Blueprint $table): void {
            $table->bigIncrements('event_id');
            $table->string('event_type', 60);
            $table->integer('sale_transaction_id')->index('customer_outbox_sale_id_index');
            $table->integer('refund_id')->nullable()->index('customer_outbox_refund_id_index');
            $table->integer('customer_id')->index('customer_outbox_customer_id_index');
            $table->json('payload');
            $table->string('status', 16)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('available_at');
            $table->dateTime('created_at');
            $table->dateTime('processed_at')->nullable();
            $table->string('last_error', 1000)->nullable();
            $table->unique(['event_type', 'sale_transaction_id', 'refund_id'], 'customer_outbox_event_idempotency_unique');
        });

        Schema::create('discount_outbox_event', function (Blueprint $table): void {
            $table->bigIncrements('event_id');
            $table->integer('sale_transaction_id')->index('discount_outbox_sale_id_index');
            $table->integer('discount_id')->index('discount_outbox_discount_id_index');
            $table->string('event_type', 60);
            $table->json('payload');
            $table->string('status', 16)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('available_at');
            $table->dateTime('created_at');
            $table->dateTime('processed_at')->nullable();
            $table->string('last_error', 1000)->nullable();
            $table->unique(['sale_transaction_id', 'discount_id', 'event_type'], 'discount_outbox_event_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_outbox_event');
        Schema::dropIfExists('customer_outbox_event');
    }
};
