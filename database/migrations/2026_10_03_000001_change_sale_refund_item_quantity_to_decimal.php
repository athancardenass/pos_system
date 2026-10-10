<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_refund_item', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sale_refund_item', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });
    }
};
