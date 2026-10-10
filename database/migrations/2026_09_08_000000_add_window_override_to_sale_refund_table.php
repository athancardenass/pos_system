<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refund window policy: record whether a refund was processed outside the
     * standard window with manager/admin authority (audit flag).
     */
    public function up(): void
    {
        Schema::table('sale_refund', function (Blueprint $table) {
            $table->boolean('window_override')->default(false)->after('is_full_refund');
        });
    }

    public function down(): void
    {
        Schema::table('sale_refund', function (Blueprint $table) {
            $table->dropColumn('window_override');
        });
    }
};
