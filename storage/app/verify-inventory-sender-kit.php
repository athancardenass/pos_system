<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$database = 'pos_webhook_kit_'.bin2hex(random_bytes(4));
$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$created = false;
$check = static function (bool $condition, string $label): void {
    if (! $condition) throw new RuntimeException($label);
    echo $label." OK\n";
};
try {
    $pdo->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config(['database.connections.sender_kit_check' => [
        'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3306,
        'database' => $database, 'username' => 'root', 'password' => '', 'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true,
    ]]);
    Illuminate\Support\Facades\DB::setDefaultConnection('sender_kit_check');
    $db = Illuminate\Support\Facades\DB::connection();
    $db->statement('CREATE TABLE products (id INT AUTO_INCREMENT PRIMARY KEY, sku VARCHAR(40) NOT NULL, name VARCHAR(150), barcode VARCHAR(40), unit_price DECIMAL(12,2), cost_price DECIMAL(12,2), reorder_point DECIMAL(12,2), status VARCHAR(20), shelf_qty DECIMAL(12,3), backroom_qty DECIMAL(12,3)) ENGINE=InnoDB');
    $migration = require __DIR__.'/../../handoff/inventory-webhook-sender/files/database/migrations/2026_10_10_190000_create_pos_webhook_outbox.php';
    $migration->up();
    $db->table('products')->insert(['sku' => 'VERIFY-1', 'name' => 'Test', 'shelf_qty' => 10, 'backroom_qty' => 0]);
    $check($db->table('pos_webhook_outbox')->count() === 1, 'Insert notification');
    $db->table('products')->where('sku', 'VERIFY-1')->update(['name' => 'Test']);
    $check($db->table('pos_webhook_outbox')->count() === 1, 'Unchanged update ignored');
    $db->table('products')->where('sku', 'VERIFY-1')->update(['shelf_qty' => 9.125]);
    $check($db->table('pos_webhook_outbox')->count() === 2, 'Stock notification');
    $db->beginTransaction();
    $db->table('products')->where('sku', 'VERIFY-1')->update(['unit_price' => 25]);
    $db->rollBack();
    $check($db->table('pos_webhook_outbox')->count() === 2, 'Rolled-back change ignored');
    $db->table('products')->where('sku', 'VERIFY-1')->delete();
    $check($db->table('pos_webhook_outbox')->count() === 3, 'Delete notification');
    $check($db->table('pos_webhook_outbox')->distinct()->count('event_id') === 3, 'Unique event identities');
} finally {
    Illuminate\Support\Facades\DB::disconnect('sender_kit_check');
    if ($created && preg_match('/^pos_webhook_kit_[a-f0-9]{8}$/D', $database)) $pdo->exec('DROP DATABASE `'.$database.'`');
}
