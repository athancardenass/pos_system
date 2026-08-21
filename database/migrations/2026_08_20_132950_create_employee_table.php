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
        Schema::create('employee', function (Blueprint $table) {
            $table->integer('employee_id', true)->index('idx_employee_id');
            $table->integer('role_id')->index('role_id');
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('username', 50)->unique('username');
            $table->string('password');
            $table->string('contact_number', 20)->nullable();
            $table->date('hire_date');
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->primary(['employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee');
    }
};
