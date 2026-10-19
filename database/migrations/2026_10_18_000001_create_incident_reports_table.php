<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 30);
            $table->unsignedInteger('employee_id')->index();
            $table->unsignedInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('drawer_id')->nullable()->unique();
            $table->string('status', 30)->default('reported');
            $table->text('description');
            $table->decimal('quantity', 12, 3)->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('actual_cash', 12, 2)->nullable();
            $table->decimal('difference', 12, 2)->nullable();
            $table->json('snapshot');
            $table->json('history');
            $table->string('evidence_path')->nullable();
            $table->text('findings')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->string('external_status', 40)->default('not_connected');
            $table->string('external_reference', 150)->nullable();
            $table->timestamps();
            $table->index(['product_id', 'kind', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};
