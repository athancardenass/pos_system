<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Supplier;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SMGroceryStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SMGroceryStoreSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(SMGroceryStoreSeeder::class);
    }

    public function test_sm_categories_are_seeded(): void
    {
        $this->assertDatabaseHas('category', [
            'category_name' => 'Value Essentials & Pantry Staples',
        ]);
        $this->assertDatabaseHas('category', [
            'category_name' => 'Fresh Produce & Fruits',
        ]);
        $this->assertDatabaseHas('category', [
            'category_name' => 'Noodles & Instant Meals',
        ]);
        $this->assertDatabaseHas('category', [
            'category_name' => 'Beverages, Juices & Coffee',
        ]);
    }

    public function test_sm_suppliers_are_seeded(): void
    {
        $this->assertDatabaseHas('supplier', [
            'supplier_name' => 'Central Retail Distribution',
        ]);
        $this->assertDatabaseHas('supplier', [
            'supplier_name' => 'Universal Robina Corporation (URC)',
        ]);
        $this->assertDatabaseHas('supplier', [
            'supplier_name' => 'Monde Nissin Corporation',
        ]);
        $this->assertDatabaseHas('supplier', [
            'supplier_name' => 'Century Pacific Food Inc.',
        ]);
    }

    public function test_sm_products_have_valid_ean13_barcodes_and_inventory(): void
    {
        $valueRice = Product::query()->where('product_name', 'Value Jasmine Rice 5kg')->first();
        $this->assertNotNull($valueRice);
        $this->assertEquals(13, strlen($valueRice->barcode));
        $this->assertEquals(
            substr($valueRice->barcode, -1),
            Product::ean13Checksum(substr($valueRice->barcode, 0, 12))
        );

        // Inventory check
        $this->assertGreaterThan(0, $valueRice->stockQuantity());

        // Check iconic goods
        $this->assertDatabaseHas('product', [
            'product_name' => 'Lucky Me! Pancit Canton Kalamansi 80g',
        ]);
        $this->assertDatabaseHas('product', [
            'product_name' => 'Purefoods Tender Juicy Hotdog Classic 1kg',
        ]);
        $this->assertDatabaseHas('product', [
            'product_name' => 'Century Tuna Flakes in Oil 155g',
        ]);
        $this->assertDatabaseHas('product', [
            'product_name' => 'Coca-Cola 1.5L PET Bottle',
        ]);
        $this->assertDatabaseHas('product', [
            'product_name' => 'Piattos Cheese Flavored Potato Crisps 85g',
        ]);
    }

    public function test_smac_loyalty_customers_are_seeded(): void
    {
        $customer = Customer::query()->where('first_name', 'Maria Clara')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('active', $customer->customer_status);
        $this->assertGreaterThan(0, $customer->loyalty_points);
    }

    public function test_sm_promotions_and_coupons_are_seeded(): void
    {
        $this->assertDatabaseHas('coupon', [
            'code' => 'LOYALTY50',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('coupon', [
            'code' => 'VALUE100',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('coupon', [
            'code' => 'WEEKENDSALE',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('promotion', [
            'name' => 'Super Weekend Sale 10% Off',
            'is_active' => true,
        ]);
    }
}
