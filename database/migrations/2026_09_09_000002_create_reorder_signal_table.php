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
        Schema::create('reorder_signal', function (Blueprint $table) {
            $table->integer('signal_id', true);
            $table->integer('product_id')->index();
            $table->enum('signal_type', ['low_stock', 'critical', 'out_of_stock', 'reorder_suggested']);
            $table->decimal('current_stock', 10, 3);
            $table->decimal('reorder_level', 10, 3);
            $table->decimal('suggested_quantity', 10, 3)->nullable();
            $table->integer('supplier_id')->nullable()->index();
            $table->enum('status', ['open', 'requested', 'po_created', 'dismissed'])->default('open');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('resolved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reorder_signal');
    }
};
