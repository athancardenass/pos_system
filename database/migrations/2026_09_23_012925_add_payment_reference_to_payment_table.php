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
        if (Schema::hasTable('payment')) {
            Schema::table('payment', function (Blueprint $table) {
                if (! Schema::hasColumn('payment', 'reference_number')) {
                    $table->string('reference_number', 100)->nullable()->after('payment_method');
                }
                if (! Schema::hasColumn('payment', 'payment_provider')) {
                    $table->string('payment_provider', 50)->nullable()->after('reference_number');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payment')) {
            Schema::table('payment', function (Blueprint $table) {
                if (Schema::hasColumn('payment', 'payment_provider')) {
                    $table->dropColumn('payment_provider');
                }
                if (Schema::hasColumn('payment', 'reference_number')) {
                    $table->dropColumn('reference_number');
                }
            });
        }
    }
};
