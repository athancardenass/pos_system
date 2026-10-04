<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_ewallet_verifications', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->integer('employee_id')->index();
            $table->string('payment_provider', 50);
            $table->string('reference_number', 100);
            $table->decimal('submitted_amount', 10, 2);
            $table->json('checkout_payload');
            $table->string('status', 20)->default('pending');
            $table->dateTime('expires_at')->index();
            $table->integer('verified_by_employee_id')->nullable()->index();
            $table->dateTime('verified_at')->nullable();
            $table->integer('rejected_by_employee_id')->nullable()->index();
            $table->dateTime('rejected_at')->nullable();
            $table->integer('sale_transaction_id')->nullable()->index();
            $table->string('resolution_note', 255)->nullable();
            $table->timestamps();

            $table->unique(['payment_provider', 'reference_number'], 'pending_ewallet_provider_reference_unique');
            $table->foreign('employee_id')->references('employee_id')->on('employee')->restrictOnDelete();
            $table->foreign('verified_by_employee_id')->references('employee_id')->on('employee')->nullOnDelete();
            $table->foreign('rejected_by_employee_id')->references('employee_id')->on('employee')->nullOnDelete();
            $table->foreign('sale_transaction_id')->references('transaction_id')->on('sale_transaction')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_ewallet_verifications');
    }
};
