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
        Schema::create('customer', function (Blueprint $table) {
            $table->integer('customer_id', true);
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('contact_number', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('address')->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->date('date_of_birth')->nullable();
            $table->decimal('total_purchases', 12)->nullable()->default(0);
            $table->enum('customer_status', ['active', 'inactive'])->nullable()->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer');
    }
};
