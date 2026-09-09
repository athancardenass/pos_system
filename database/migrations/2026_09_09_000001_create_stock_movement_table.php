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
        Schema::create('stock_movement', function (Blueprint $table) {
            $table->integer('movement_id', true);
            $table->integer('product_id')->index();
            $table->enum('movement_type', ['sale', 'purchase', 'refund', 'adjustment', 'return', 'damage']);
            $table->decimal('quantity', 10, 3);
            $table->decimal('stock_before', 10, 3);
            $table->decimal('stock_after', 10, 3);
            $table->string('reference_type', 50)->nullable();
            $table->integer('reference_id')->nullable();
            $table->string('reason')->nullable();
            $table->integer('employee_id')->nullable()->index();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movement');
    }
};
