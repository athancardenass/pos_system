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
        Schema::create('audit_log', function (Blueprint $table) {
            $table->integer('log_id', true);
            $table->integer('employee_id')->index('idx_audit_employee');
            $table->string('action', 100);
            $table->string('table_affected', 100)->nullable();
            $table->integer('record_id')->nullable();
            $table->dateTime('action_timestamp')->useCurrent();
            $table->string('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
