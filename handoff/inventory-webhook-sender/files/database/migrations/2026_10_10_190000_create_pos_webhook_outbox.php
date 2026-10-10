<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('This Inventory sender requires MySQL/MariaDB triggers.');
        }
        if (! Schema::hasColumns('products', ['sku', 'name', 'barcode', 'unit_price', 'cost_price', 'reorder_point', 'status', 'shelf_qty', 'backroom_qty'])) {
            throw new RuntimeException('Inventory products schema does not match this sender kit.');
        }
        if (! Schema::hasTable('pos_webhook_outbox')) {
            Schema::create('pos_webhook_outbox', function (Blueprint $table) {
                $table->char('event_id', 36)->primary();
                $table->string('sku', 100);
                $table->unsignedInteger('attempts')->default(0);
                $table->dateTime('available_at')->index();
                $table->dateTime('delivered_at')->nullable();
                $table->string('last_error', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        } elseif (! Schema::hasColumns('pos_webhook_outbox', ['event_id', 'sku', 'attempts', 'available_at', 'delivered_at', 'last_error', 'created_at'])) {
            throw new RuntimeException('Existing pos_webhook_outbox has an incompatible schema.');
        }
        foreach (['insert', 'update', 'delete'] as $operation) {
            DB::unprepared('DROP TRIGGER IF EXISTS pos_webhook_products_'.$operation);
        }
        DB::unprepared(<<<'SQL'
CREATE TRIGGER pos_webhook_products_insert AFTER INSERT ON products FOR EACH ROW
INSERT INTO pos_webhook_outbox (event_id, sku, available_at, created_at) VALUES (UUID(), NEW.sku, NOW(), NOW())
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER pos_webhook_products_update AFTER UPDATE ON products FOR EACH ROW
BEGIN
    IF NOT (OLD.sku <=> NEW.sku) OR NOT (OLD.name <=> NEW.name)
       OR NOT (OLD.barcode <=> NEW.barcode) OR NOT (OLD.unit_price <=> NEW.unit_price)
       OR NOT (OLD.cost_price <=> NEW.cost_price) OR NOT (OLD.reorder_point <=> NEW.reorder_point)
       OR NOT (OLD.status <=> NEW.status) OR NOT (OLD.shelf_qty <=> NEW.shelf_qty)
       OR NOT (OLD.backroom_qty <=> NEW.backroom_qty) THEN
        INSERT INTO pos_webhook_outbox (event_id, sku, available_at, created_at) VALUES (UUID(), NEW.sku, NOW(), NOW());
    END IF;
END
SQL);
        DB::unprepared(<<<'SQL'
CREATE TRIGGER pos_webhook_products_delete AFTER DELETE ON products FOR EACH ROW
INSERT INTO pos_webhook_outbox (event_id, sku, available_at, created_at) VALUES (UUID(), OLD.sku, NOW(), NOW())
SQL);
    }

    public function down(): void
    {
        foreach (['insert', 'update', 'delete'] as $operation) {
            DB::unprepared('DROP TRIGGER IF EXISTS pos_webhook_products_'.$operation);
        }
        Schema::dropIfExists('pos_webhook_outbox');
    }
};
