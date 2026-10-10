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
        Schema::create('discount', function (Blueprint $table) {
            $table->integer('discount_id', true);
            $table->string('discount_name', 100);
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 10);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount');
    }
};
