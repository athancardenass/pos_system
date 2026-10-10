<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_outbox_event')) {
            Schema::create('inventory_outbox_event', function (Blueprint $table): void {
                $table->bigIncrements('event_id');
                $table->string('event_type', 60);
                $table->integer('sale_transaction_id')->index('inventory_outbox_sale_id_index');
                $table->integer('refund_id')->nullable()->index('inventory_outbox_refund_id_index');
                $table->integer('product_id')->index('inventory_outbox_product_id_index');
                $table->decimal('quantity', 10, 3);
                $table->json('payload');
                $table->string('status', 16)->default('pending')->index();
                $table->unsignedInteger('attempts')->default(0);
                $table->dateTime('available_at');
                $table->dateTime('created_at');
                $table->dateTime('processed_at')->nullable();
                $table->string('last_error', 1000)->nullable();
                $table->unique(['event_type', 'sale_transaction_id', 'refund_id', 'product_id'], 'inventory_outbox_event_idempotency_unique');
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_outbox_event');
    }
};
