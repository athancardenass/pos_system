<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->string('manager_pin_hash')->nullable()->after('password');
        });

        Schema::table('audit_log', function (Blueprint $table) {
            $table->integer('requested_by_employee_id')->nullable()->after('employee_id');
            $table->integer('approved_by_employee_id')->nullable()->after('requested_by_employee_id');
            $table->string('register_id', 50)->nullable()->after('approved_by_employee_id');
            $table->json('details')->nullable()->after('description');

            $table->foreign('requested_by_employee_id', 'audit_log_requested_by_fk')
                ->references('employee_id')->on('employee')->nullOnDelete();
            $table->foreign('approved_by_employee_id', 'audit_log_approved_by_fk')
                ->references('employee_id')->on('employee')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropForeign('audit_log_requested_by_fk');
            $table->dropForeign('audit_log_approved_by_fk');
            $table->dropColumn([
                'requested_by_employee_id',
                'approved_by_employee_id',
                'register_id',
                'details',
            ]);
        });

        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('manager_pin_hash');
        });
    }
};
