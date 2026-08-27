<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed a demo product catalog.
     *
     * Each product is linked to a seeded category and supplier, and gets an
     * inventory row (stock_quantity > 0) so the POS is immediately usable —
     * this mirrors the inventory creation done in ProductController@store.
     */
    public function run(): void
    {
        $beverages = Category::query()->where('category_name', 'Beverages')->firstOrFail();
        $snacks = Category::query()->where('category_name', 'Snacks')->firstOrFail();
        $personal = Category::query()->where('category_name', 'Personal Care')->firstOrFail();
        $household = Category::query()->where('category_name', 'Household')->firstOrFail();
        $stationery = Category::query()->where('category_name', 'Stationery')->firstOrFail();

        $metro = Supplier::query()->where('supplier_name', 'Metro Wholesale')->firstOrFail();
        $prime = Supplier::query()->where('supplier_name', 'Prime Distributors')->firstOrFail();
        $fresh = Supplier::query()->where('supplier_name', 'FreshLink Trading')->firstOrFail();

        $products = [
            ['product_name' => 'Bottled Water 500ml', 'category' => $beverages, 'supplier' => $fresh, 'unit_price' => 15.00, 'cost_price' => 8.00, 'barcode' => 'BR-0001', 'stock' => 120, 'reorder' => 30],
            ['product_name' => 'Cola 350ml', 'category' => $beverages, 'supplier' => $metro, 'unit_price' => 25.00, 'cost_price' => 14.00, 'barcode' => 'BR-0002', 'stock' => 90, 'reorder' => 25],
            ['product_name' => 'Orange Juice 1L', 'category' => $beverages, 'supplier' => $metro, 'unit_price' => 65.00, 'cost_price' => 40.00, 'barcode' => 'BR-0003', 'stock' => 40, 'reorder' => 15],
            ['product_name' => 'Potato Chips 100g', 'category' => $snacks, 'supplier' => $prime, 'unit_price' => 30.00, 'cost_price' => 18.00, 'barcode' => 'SN-0001', 'stock' => 75, 'reorder' => 20],
            ['product_name' => 'Chocolate Bar', 'category' => $snacks, 'supplier' => $prime, 'unit_price' => 35.00, 'cost_price' => 20.00, 'barcode' => 'SN-0002', 'stock' => 60, 'reorder' => 20],
            ['product_name' => 'Bath Soap', 'category' => $personal, 'supplier' => $fresh, 'unit_price' => 28.00, 'cost_price' => 15.00, 'barcode' => 'PC-0001', 'stock' => 50, 'reorder' => 15],
            ['product_name' => 'Shampoo 200ml', 'category' => $personal, 'supplier' => $fresh, 'unit_price' => 85.00, 'cost_price' => 50.00, 'barcode' => 'PC-0002', 'stock' => 35, 'reorder' => 10],
            ['product_name' => 'Dishwashing Liquid 500ml', 'category' => $household, 'supplier' => $metro, 'unit_price' => 55.00, 'cost_price' => 32.00, 'barcode' => 'HH-0001', 'stock' => 45, 'reorder' => 12],
            ['product_name' => 'Paper Towels 2-ply', 'category' => $household, 'supplier' => $metro, 'unit_price' => 45.00, 'cost_price' => 26.00, 'barcode' => 'HH-0002', 'stock' => 30, 'reorder' => 10],
            ['product_name' => 'Ballpen (Blue)', 'category' => $stationery, 'supplier' => $prime, 'unit_price' => 12.00, 'cost_price' => 5.00, 'barcode' => 'ST-0001', 'stock' => 200, 'reorder' => 50],
            ['product_name' => 'Notebook A5', 'category' => $stationery, 'supplier' => $prime, 'unit_price' => 40.00, 'cost_price' => 22.00, 'barcode' => 'ST-0002', 'stock' => 80, 'reorder' => 25],
        ];

        foreach ($products as $item) {
            $product = Product::query()->firstOrCreate(
                ['barcode' => $item['barcode']],
                [
                    'category_id' => $item['category']->category_id,
                    'supplier_id' => $item['supplier']->supplier_id,
                    'product_name' => $item['product_name'],
                    'unit_price' => $item['unit_price'],
                    'cost_price' => $item['cost_price'],
                    'reorder_level' => $item['reorder'],
                ],
            );

            // Create the inventory row if it does not exist yet (mirrors ProductController@store).
            Inventory::query()->firstOrCreate(
                ['product_id' => $product->product_id],
                ['stock_quantity' => $item['stock']],
            );
        }
    }
}
