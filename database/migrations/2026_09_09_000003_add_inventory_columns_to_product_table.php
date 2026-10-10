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
        Schema::table('product', function (Blueprint $table) {
            $table->enum('unit_of_measure', ['piece', 'kg', 'g', 'ml', 'l', 'box', 'pack'])->default('piece')->after('reorder_level');
            $table->decimal('critical_reorder_level', 10, 3)->default(0)->after('reorder_level');
            $table->boolean('is_active')->default(true)->after('unit_of_measure');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn(['unit_of_measure', 'critical_reorder_level', 'is_active']);
        });
    }
};
