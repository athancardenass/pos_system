<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_webhook_inbox', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 120)->unique();
            $table->string('sku', 100);
            $table->char('payload_hash', 64);
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('available_at')->index();
            $table->dateTime('processed_at')->nullable();
            $table->string('last_error', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void { Schema::dropIfExists('inventory_webhook_inbox'); }
};
