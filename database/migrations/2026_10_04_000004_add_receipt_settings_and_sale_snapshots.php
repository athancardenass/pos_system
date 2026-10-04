<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_transaction', function (Blueprint $table): void {
            $table->string('senior_pwd_type', 20)->nullable();
            $table->string('senior_pwd_name', 150)->nullable();
            $table->string('senior_pwd_id_number', 80)->nullable();
            $table->json('discount_snapshot')->nullable();
            $table->json('tax_snapshot')->nullable();
        });

        Schema::create('receipt_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('store_name', 150)->default('Your Store');
            $table->text('store_address')->nullable();
            $table->string('tin', 50)->nullable();
            $table->string('paper_width', 2)->default('58');
            $table->text('footer_text')->nullable();
            $table->timestamps();
        });

        Schema::create('register_receipt_counters', function (Blueprint $table): void {
            $table->string('register_id', 50)->primary();
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();
        });

        Schema::table('receipt', function (Blueprint $table): void {
            $table->string('register_id', 50)->nullable();
            $table->unsignedInteger('sequence_number')->nullable();
            $table->json('settings_snapshot')->nullable();
            $table->unique(['register_id', 'sequence_number'], 'receipt_register_sequence_unique');
        });

        DB::table('receipt_settings')->insert([
            'id' => 1,
            'store_name' => 'Your Store',
            'paper_width' => '58',
            'footer_text' => 'Thank you for shopping with us.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('receipt', function (Blueprint $table): void {
            $table->dropUnique('receipt_register_sequence_unique');
            $table->dropColumn(['register_id', 'sequence_number', 'settings_snapshot']);
        });

        Schema::dropIfExists('register_receipt_counters');
        Schema::dropIfExists('receipt_settings');

        Schema::table('sale_transaction', function (Blueprint $table): void {
            $table->dropColumn([
                'senior_pwd_type',
                'senior_pwd_name',
                'senior_pwd_id_number',
                'discount_snapshot',
                'tax_snapshot',
            ]);
        });
    }
};
