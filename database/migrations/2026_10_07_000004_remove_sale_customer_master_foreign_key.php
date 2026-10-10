<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $foreignKeys = Schema::getForeignKeys('sale_transaction');
            $hasCustomerForeignKey = collect($foreignKeys)->contains(
                fn (array $foreignKey): bool => ($foreignKey['columns'] ?? []) === ['customer_id']
            );

            if ($hasCustomerForeignKey) {
                Schema::table('sale_transaction', function (Blueprint $table): void {
                    $table->dropForeign(['customer_id']);
                });
            }

            return;
        }

        if (DB::table('migrations')->where('migration', '2026_08_20_132953_add_foreign_keys_to_sale_transaction_table')->exists()
            && DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', 'sale_transaction')
                ->where('CONSTRAINT_NAME', 'sale_transaction_ibfk_1')
                ->exists()) {
            Schema::table('sale_transaction', function (Blueprint $table): void {
                $table->dropForeign('sale_transaction_ibfk_1');
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            if (! Schema::hasTable('customer')) {
                return;
            }

            $hasCustomerForeignKey = collect(Schema::getForeignKeys('sale_transaction'))->contains(
                fn (array $foreignKey): bool => ($foreignKey['columns'] ?? []) === ['customer_id']
            );
            if ($hasCustomerForeignKey) {
                return;
            }
        }

        Schema::table('sale_transaction', function (Blueprint $table): void {
            $table->foreign('customer_id', 'sale_transaction_ibfk_1')
                ->references('customer_id')
                ->on('customer')
                ->nullOnDelete();
        });
    }
};
