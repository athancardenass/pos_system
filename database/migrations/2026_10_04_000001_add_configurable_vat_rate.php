<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultRate = (float) config('vat.rate', 0.12);

        Schema::create('vat_settings', function (Blueprint $table) use ($defaultRate): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->decimal('rate', 5, 4)->default($defaultRate);
            $table->timestamps();
        });

        DB::table('vat_settings')->insert([
            'id' => 1,
            'rate' => $defaultRate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('sale_transaction', function (Blueprint $table) use ($defaultRate): void {
            $table->decimal('vat_rate', 5, 4)->default($defaultRate)->after('total_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_transaction', function (Blueprint $table): void {
            $table->dropColumn('vat_rate');
        });

        Schema::dropIfExists('vat_settings');
    }
};
