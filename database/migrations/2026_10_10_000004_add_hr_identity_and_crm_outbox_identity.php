<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->string('hrms_employee_no', 100)->nullable()->unique();
            $table->string('hrms_employment_status', 20)->nullable();
            $table->string('hrms_position', 150)->nullable();
            $table->dateTime('hrms_synced_at')->nullable();
        });
        Schema::table('customer_outbox_event', fn (Blueprint $table) => $table->string('crm_external_id', 120)->nullable()->index());
    }

    public function down(): void
    {
        Schema::table('customer_outbox_event', fn (Blueprint $table) => $table->dropColumn('crm_external_id'));
        Schema::table('employee', function (Blueprint $table) {
            $table->dropUnique(['hrms_employee_no']);
            $table->dropColumn(['hrms_employee_no', 'hrms_employment_status', 'hrms_position', 'hrms_synced_at']);
        });
    }
};
