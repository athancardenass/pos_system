<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('purchase_order_details');
        Schema::dropIfExists('purchase_order');
    }

    public function down(): void
    {
        // Restore structure only. Recover deleted records from the SQL backup.
        foreach ([
            '2026_08_20_132950_create_purchase_order_table.php',
            '2026_08_20_132950_create_purchase_order_details_table.php',
            '2026_08_20_132953_add_foreign_keys_to_purchase_order_table.php',
            '2026_08_20_132953_add_foreign_keys_to_purchase_order_details_table.php',
            '2026_09_09_000006_change_purchase_order_details_quantity_to_decimal.php',
        ] as $file) {
            (require __DIR__.'/'.$file)->up();
        }
    }
};
