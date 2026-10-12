<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_drawers', function (Blueprint $t) {
            $t->decimal('ledger_start_cash', 12, 2)->nullable();
            $t->text('reconciliation_notes')->nullable();
            $t->json('report_snapshot')->nullable();
        });
        DB::table('cash_drawers')->update(['ledger_start_cash' => DB::raw('expected_cash')]);
        Schema::create('drawer_transfers', function (Blueprint $t) {
            $t->id();
            $t->uuid('operation_key')->unique();
            $t->foreignId('source_drawer_id')->constrained('cash_drawers', 'drawer_id');
            $t->foreignId('destination_drawer_id')->constrained('cash_drawers', 'drawer_id');
            $t->decimal('amount', 12, 2);
            $t->string('reason', 255);
            $t->integer('approved_by_employee_id');
            $t->foreign('approved_by_employee_id')->references('employee_id')->on('employee');
            $t->string('status', 20)->default('pending');
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
        });
        Schema::table('sale_refund', function (Blueprint $t) {
            $t->uuid('operation_key')->nullable()->unique();
            $t->string('request_fingerprint', 64)->nullable();
            $t->foreignId('drawer_id')->nullable()->constrained('cash_drawers', 'drawer_id');
            $t->string('payment_method', 20)->nullable();
            $t->string('settlement_reference', 100)->nullable();
        });
        Schema::table('sale_refund_item', fn (Blueprint $t) => $t->string('condition', 20)->default('resellable'));
        Schema::create('sale_exchanges', function (Blueprint $t) {
            $t->id();
            $t->uuid('operation_key')->unique();
            $t->string('request_fingerprint', 64);
            $t->integer('original_sale_id');
            $t->foreign('original_sale_id')->references('transaction_id')->on('sale_transaction');
            $t->integer('replacement_sale_id')->unique();
            $t->foreign('replacement_sale_id')->references('transaction_id')->on('sale_transaction');
            $t->integer('refund_id')->unique();
            $t->foreign('refund_id')->references('refund_id')->on('sale_refund');
            $t->foreignId('drawer_id')->constrained('cash_drawers', 'drawer_id');
            $t->decimal('difference', 12, 2);
            $t->string('payment_method', 20);
            $t->string('settlement_reference', 100)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
        Schema::create('drawer_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('drawer_id')->constrained('cash_drawers', 'drawer_id');
            $t->string('operation_key', 120)->unique();
            $t->string('kind', 30);
            $t->string('payment_method', 20)->default('cash');
            $t->decimal('amount', 12, 2);
            $t->string('reason', 255)->nullable();
            $t->integer('approved_by_employee_id')->nullable();
            $t->foreign('approved_by_employee_id')->references('employee_id')->on('employee');
            $t->integer('sale_id')->nullable();
            $t->foreign('sale_id')->references('transaction_id')->on('sale_transaction');
            $t->integer('refund_id')->nullable();
            $t->foreign('refund_id')->references('refund_id')->on('sale_refund');
            $t->foreignId('transfer_id')->nullable()->constrained('drawer_transfers');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['drawer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drawer_movements');
        Schema::dropIfExists('sale_exchanges');
        Schema::table('sale_refund_item', fn (Blueprint $t) => $t->dropColumn('condition'));
        Schema::table('sale_refund', function (Blueprint $t) {
            $t->dropForeign(['drawer_id']);
            $t->dropUnique(['operation_key']);
            $t->dropColumn(['operation_key', 'request_fingerprint', 'drawer_id', 'payment_method', 'settlement_reference']);
        });
        Schema::dropIfExists('drawer_transfers');
        Schema::table('cash_drawers', fn (Blueprint $t) => $t->dropColumn(['ledger_start_cash', 'reconciliation_notes', 'report_snapshot']));
    }
};
