<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('role')->insertOrIgnore(['role_name' => 'Supervisor']);

        Schema::create('supervisor_action_permission', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id')->index();
            $table->string('action', 50);
            $table->decimal('max_amount', 12, 2);
            $table->timestamps();
            $table->unique(['employee_id', 'action'], 'supervisor_permission_employee_action_unique');
            $table->foreign('employee_id', 'supervisor_permission_employee_fk')
                ->references('employee_id')->on('employee')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_action_permission');
        if (! DB::table('employee')->whereIn('role_id', DB::table('role')->select('role_id')->where('role_name', 'Supervisor'))->exists()) {
            DB::table('role')->where('role_name', 'Supervisor')->delete();
        }
    }
};
