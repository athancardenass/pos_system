<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PurchaseOrder;
use App\Models\Receipt;
use App\Models\SaleTransaction;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class GroceryStoreSeeder extends Seeder
{
    /**
     * Compute a 13-digit EAN-13 barcode using a 12-digit base.
     */
    private function makeEan13(string $base12): string
    {
        return $base12 . Product::ean13Checksum($base12);
    }

    public function run(): void
    {
        // -------------------------------------------------------------
        // 1. Supermarket Categories
        // -------------------------------------------------------------
        $categoriesData = [
            ['category_name' => 'Value Essentials & Pantry Staples', 'description' => 'Private label value pantry and household staples'],
            ['category_name' => 'Fresh Produce & Fruits', 'description' => 'Fresh vegetables, farm fruits, and organic crops'],
            ['category_name' => 'Fresh Meat & Poultry', 'description' => 'Dressed chicken, premium pork cuts, and beef sirloin'],
            ['category_name' => 'Seafood Market', 'description' => 'Fresh bangus, tilapia, shrimp, and chilled fish'],
            ['category_name' => 'Dairy, Chilled & Eggs', 'description' => 'Fresh milk, cheese, butter, yogurt, and farm fresh eggs'],
            ['category_name' => 'Breakfast & Bakery', 'description' => 'Sliced bread, cereals, spreads, and pastries'],
            ['category_name' => 'Rice, Grains & Cooking Condiments', 'description' => 'Jasmine rice, cooking oils, soy sauces, vinegar, and seasonings'],
            ['category_name' => 'Canned Goods & Preserves', 'description' => 'Canned tuna, corned beef, sardines, and tomato sauces'],
            ['category_name' => 'Noodles & Instant Meals', 'description' => 'Pancit canton, instant ramen, pasta, and cup noodles'],
            ['category_name' => 'Snacks, Chips & Biscuits', 'description' => 'Potato chips, crackers, chocolate snacks, and native biscuits'],
            ['category_name' => 'Beverages, Juices & Coffee', 'description' => 'Colas, iced tea, 3-in-1 coffee, chocolate malt, and fruit juices'],
            ['category_name' => 'Beer & Liquors', 'description' => 'Local beers, wines, and chilled spirits'],
            ['category_name' => 'Household & Cleaning Supplies', 'description' => 'Detergent powders, dishwashing liquids, bleach, and fabric care'],
            ['category_name' => 'Personal Care & Grooming', 'description' => 'Bath soaps, shampoos, oral care, and deodorants'],
            ['category_name' => 'Baby Care Essentials', 'description' => 'Diapers, baby powder, gentle body wash, and wipes'],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['category_name']] = Category::query()->firstOrCreate(
                ['category_name' => $cat['category_name']],
                ['description' => $cat['description']],
            );
        }

        // -------------------------------------------------------------
        // 2. Major Philippine FMCG & Supermarket Suppliers
        // -------------------------------------------------------------
        $suppliersData = [
            ['supplier_name' => 'Central Retail Distribution', 'contact_number' => '02-8831-1000', 'email' => 'orders@centralretail.example.ph', 'address' => 'Pasay City, Metro Manila'],
            ['supplier_name' => 'Universal Robina Corporation (URC)', 'contact_number' => '02-8633-7631', 'email' => 'sales@urc.example.ph', 'address' => 'Pasig City, Metro Manila'],
            ['supplier_name' => 'San Miguel Food & Beverage Inc.', 'contact_number' => '02-8632-2000', 'email' => 'fmcg@sanmiguel.example.ph', 'address' => 'Mandaluyong City, Metro Manila'],
            ['supplier_name' => 'Monde Nissin Corporation', 'contact_number' => '02-8759-7500', 'email' => 'distribution@mondenissin.example.ph', 'address' => 'Santa Rosa, Laguna'],
            ['supplier_name' => 'Nestle Philippines Inc.', 'contact_number' => '02-8898-0001', 'email' => 'supermarkets@ph.nestle.example', 'address' => 'Makati City, Metro Manila'],
            ['supplier_name' => 'Unilever Philippines Inc.', 'contact_number' => '02-8588-8800', 'email' => 'orders@unilever.example.ph', 'address' => 'Taguig City, Metro Manila'],
            ['supplier_name' => 'Procter & Gamble Philippines', 'contact_number' => '02-8894-8000', 'email' => 'retail@pg.example.ph', 'address' => 'BGC, Taguig City'],
            ['supplier_name' => 'NutriAsia Inc.', 'contact_number' => '02-8635-4600', 'email' => 'condiments@nutriasia.example.ph', 'address' => 'Taguig City, Metro Manila'],
            ['supplier_name' => 'Century Pacific Food Inc.', 'contact_number' => '02-8633-8555', 'email' => 'canned@centurypacific.example.ph', 'address' => 'Pasig City, Metro Manila'],
            ['supplier_name' => 'Del Monte Philippines Inc.', 'contact_number' => '02-8856-2888', 'email' => 'supply@delmonte-ph.example', 'address' => 'BGC, Taguig City'],
            ['supplier_name' => 'Liwayway Marketing Corporation (Oishi)', 'contact_number' => '02-8844-8441', 'email' => 'orders@oishi.example.ph', 'address' => 'Cavite Light Industrial Park'],
        ];

        $suppliers = [];
        foreach ($suppliersData as $sup) {
            $suppliers[$sup['supplier_name']] = Supplier::query()->firstOrCreate(
                ['supplier_name' => $sup['supplier_name']],
                $sup,
            );
        }

        // -------------------------------------------------------------
        // 3. Supermarket Product Catalog (60+ Authentic Philippine Goods)
        // -------------------------------------------------------------
        // Note: Using 480... Philippines GS1 country code prefix with calculated EAN-13 check digit.
        $catalog = [
            // Value Essentials Line
            [
                'name' => 'Value Jasmine Rice 5kg',
                'barcode' => $this->makeEan13('480000100001'),
                'cat' => 'Value Essentials & Pantry Staples',
                'sup' => 'Central Retail Distribution',
                'price' => 265.00, 'cost' => 210.00, 'reorder' => 15, 'unit' => 'bag', 'stock' => 80,
            ],
            [
                'name' => 'Value Purified Water 500ml',
                'barcode' => $this->makeEan13('480000100002'),
                'cat' => 'Value Essentials & Pantry Staples',
                'sup' => 'Central Retail Distribution',
                'price' => 11.50, 'cost' => 6.50, 'reorder' => 50, 'unit' => 'bottle', 'stock' => 240,
            ],
            [
                'name' => 'Value Pure Vegetable Oil 1L',
                'barcode' => $this->makeEan13('480000100003'),
                'cat' => 'Value Essentials & Pantry Staples',
                'sup' => 'Central Retail Distribution',
                'price' => 89.00, 'cost' => 68.00, 'reorder' => 20, 'unit' => 'bottle', 'stock' => 90,
            ],
            [
                'name' => 'Value Refined White Sugar 1kg',
                'barcode' => $this->makeEan13('480000100004'),
                'cat' => 'Value Essentials & Pantry Staples',
                'sup' => 'Central Retail Distribution',
                'price' => 78.00, 'cost' => 60.00, 'reorder' => 25, 'unit' => 'pack', 'stock' => 110,
            ],
            [
                'name' => 'Value Dishwashing Liquid Lemon 500ml',
                'barcode' => $this->makeEan13('480000100005'),
                'cat' => 'Value Essentials & Pantry Staples',
                'sup' => 'Central Retail Distribution',
                'price' => 49.50, 'cost' => 32.00, 'reorder' => 20, 'unit' => 'bottle', 'stock' => 85,
            ],
            [
                'name' => 'Value Bleach Regular 1L',
                'barcode' => $this->makeEan13('480000100006'),
                'cat' => 'Value Essentials & Pantry Staples',
                'sup' => 'Central Retail Distribution',
                'price' => 38.00, 'cost' => 24.00, 'reorder' => 15, 'unit' => 'bottle', 'stock' => 70,
            ],

            // Fresh Produce & Meat
            [
                'name' => 'Cavendish Banana Fresh (per kg)',
                'barcode' => $this->makeEan13('480000200001'),
                'cat' => 'Fresh Produce & Fruits',
                'sup' => 'Central Retail Distribution',
                'price' => 75.00, 'cost' => 48.00, 'reorder' => 15, 'unit' => 'kg', 'stock' => 50,
            ],
            [
                'name' => 'Fuji Apple Sweet (per piece)',
                'barcode' => $this->makeEan13('480000200002'),
                'cat' => 'Fresh Produce & Fruits',
                'sup' => 'Central Retail Distribution',
                'price' => 25.00, 'cost' => 16.00, 'reorder' => 30, 'unit' => 'pc', 'stock' => 120,
            ],
            [
                'name' => 'Red Onion Native (per kg)',
                'barcode' => $this->makeEan13('480000200003'),
                'cat' => 'Fresh Produce & Fruits',
                'sup' => 'Central Retail Distribution',
                'price' => 140.00, 'cost' => 95.00, 'reorder' => 10, 'unit' => 'kg', 'stock' => 40,
            ],
            [
                'name' => 'Garlic Native Whole (per kg)',
                'barcode' => $this->makeEan13('480000200004'),
                'cat' => 'Fresh Produce & Fruits',
                'sup' => 'Central Retail Distribution',
                'price' => 130.00, 'cost' => 85.00, 'reorder' => 10, 'unit' => 'kg', 'stock' => 35,
            ],
            [
                'name' => 'Carabao Ripe Mango (per kg)',
                'barcode' => $this->makeEan13('480000200005'),
                'cat' => 'Fresh Produce & Fruits',
                'sup' => 'Central Retail Distribution',
                'price' => 160.00, 'cost' => 110.00, 'reorder' => 10, 'unit' => 'kg', 'stock' => 30,
            ],
            [
                'name' => 'Fresh Whole Magnolia Chicken (per kg)',
                'barcode' => $this->makeEan13('480000200006'),
                'cat' => 'Fresh Meat & Poultry',
                'sup' => 'San Miguel Food & Beverage Inc.',
                'price' => 195.00, 'cost' => 145.00, 'reorder' => 12, 'unit' => 'kg', 'stock' => 45,
            ],
            [
                'name' => 'Pork Liempo Premium Cut (per kg)',
                'barcode' => $this->makeEan13('480000200007'),
                'cat' => 'Fresh Meat & Poultry',
                'sup' => 'San Miguel Food & Beverage Inc.',
                'price' => 340.00, 'cost' => 260.00, 'reorder' => 8, 'unit' => 'kg', 'stock' => 30,
            ],
            [
                'name' => 'Fresh Bangus Milkfish Medium (per kg)',
                'barcode' => $this->makeEan13('480000200008'),
                'cat' => 'Seafood Market',
                'sup' => 'Central Retail Distribution',
                'price' => 210.00, 'cost' => 155.00, 'reorder' => 8, 'unit' => 'kg', 'stock' => 25,
            ],

            // Dairy, Chilled & Processed Meat
            [
                'name' => 'Purefoods Tender Juicy Hotdog Classic 1kg',
                'barcode' => $this->makeEan13('480000300001'),
                'cat' => 'Dairy, Chilled & Eggs',
                'sup' => 'San Miguel Food & Beverage Inc.',
                'price' => 245.00, 'cost' => 188.00, 'reorder' => 15, 'unit' => 'pack', 'stock' => 60,
            ],
            [
                'name' => 'CDO Idol Cheesedog 500g',
                'barcode' => $this->makeEan13('480000300002'),
                'cat' => 'Dairy, Chilled & Eggs',
                'sup' => 'Central Retail Distribution',
                'price' => 115.00, 'cost' => 88.00, 'reorder' => 15, 'unit' => 'pack', 'stock' => 50,
            ],
            [
                'name' => 'Magnolia Pure Fresh Milk 1L',
                'barcode' => $this->makeEan13('480000300003'),
                'cat' => 'Dairy, Chilled & Eggs',
                'sup' => 'San Miguel Food & Beverage Inc.',
                'price' => 98.00, 'cost' => 74.00, 'reorder' => 20, 'unit' => 'tetra', 'stock' => 70,
            ],
            [
                'name' => 'Bear Brand Fortified Powdered Milk 900g',
                'barcode' => $this->makeEan13('480000300004'),
                'cat' => 'Dairy, Chilled & Eggs',
                'sup' => 'Nestle Philippines Inc.',
                'price' => 295.00, 'cost' => 235.00, 'reorder' => 15, 'unit' => 'pack', 'stock' => 65,
            ],
            [
                'name' => 'Eden Cheese Original 165g',
                'barcode' => $this->makeEan13('480000300005'),
                'cat' => 'Dairy, Chilled & Eggs',
                'sup' => 'Monde Nissin Corporation',
                'price' => 62.00, 'cost' => 46.00, 'reorder' => 25, 'unit' => 'box', 'stock' => 95,
            ],
            [
                'name' => 'Kraft Cheez Whiz Original 210g Jar',
                'barcode' => $this->makeEan13('480000300006'),
                'cat' => 'Dairy, Chilled & Eggs',
                'sup' => 'Monde Nissin Corporation',
                'price' => 89.00, 'cost' => 68.00, 'reorder' => 20, 'unit' => 'jar', 'stock' => 75,
            ],
            [
                'name' => 'Yakult Probiotic Drink (Pack of 5)',
                'barcode' => $this->makeEan13('480000300007'),
                'cat' => 'Dairy, Chilled & Eggs',
                'sup' => 'Central Retail Distribution',
                'price' => 58.00, 'cost' => 44.00, 'reorder' => 30, 'unit' => 'pack', 'stock' => 120,
            ],
            [
                'name' => 'Magnolia Gold Butter Salted 225g',
                'barcode' => $this->makeEan13('480000300008'),
                'cat' => 'Dairy, Chilled & Eggs',
                'sup' => 'San Miguel Food & Beverage Inc.',
                'price' => 145.00, 'cost' => 112.00, 'reorder' => 10, 'unit' => 'bar', 'stock' => 40,
            ],

            // Rice, Cooking & Pantry Condiments
            [
                'name' => 'Sinandomeng Special Rice 10kg',
                'barcode' => $this->makeEan13('480000400001'),
                'cat' => 'Rice, Grains & Cooking Condiments',
                'sup' => 'Central Retail Distribution',
                'price' => 520.00, 'cost' => 430.00, 'reorder' => 10, 'unit' => 'sack', 'stock' => 40,
            ],
            [
                'name' => 'Datu Puti Soy Sauce 1L Bottle',
                'barcode' => $this->makeEan13('480000400002'),
                'cat' => 'Rice, Grains & Cooking Condiments',
                'sup' => 'NutriAsia Inc.',
                'price' => 45.00, 'cost' => 32.00, 'reorder' => 25, 'unit' => 'bottle', 'stock' => 85,
            ],
            [
                'name' => 'Datu Puti White Cane Vinegar 1L',
                'barcode' => $this->makeEan13('480000400003'),
                'cat' => 'Rice, Grains & Cooking Condiments',
                'sup' => 'NutriAsia Inc.',
                'price' => 38.00, 'cost' => 26.00, 'reorder' => 25, 'unit' => 'bottle', 'stock' => 80,
            ],
            [
                'name' => 'Silver Swan Soy Sauce 1L Pet',
                'barcode' => $this->makeEan13('480000400004'),
                'cat' => 'Rice, Grains & Cooking Condiments',
                'sup' => 'NutriAsia Inc.',
                'price' => 44.00, 'cost' => 31.00, 'reorder' => 25, 'unit' => 'bottle', 'stock' => 75,
            ],
            [
                'name' => 'UFC Tamis Anghang Banana Ketchup 320g',
                'barcode' => $this->makeEan13('480000400005'),
                'cat' => 'Rice, Grains & Cooking Condiments',
                'sup' => 'NutriAsia Inc.',
                'price' => 28.00, 'cost' => 19.50, 'reorder' => 30, 'unit' => 'bottle', 'stock' => 90,
            ],
            [
                'name' => 'Mang Tomas All-Around Sarsa 325g',
                'barcode' => $this->makeEan13('480000400006'),
                'cat' => 'Rice, Grains & Cooking Condiments',
                'sup' => 'NutriAsia Inc.',
                'price' => 39.50, 'cost' => 28.00, 'reorder' => 20, 'unit' => 'bottle', 'stock' => 85,
            ],
            [
                'name' => 'Golden Fiesta Canola Oil 1L',
                'barcode' => $this->makeEan13('480000400007'),
                'cat' => 'Rice, Grains & Cooking Condiments',
                'sup' => 'NutriAsia Inc.',
                'price' => 135.00, 'cost' => 102.00, 'reorder' => 15, 'unit' => 'bottle', 'stock' => 55,
            ],
            [
                'name' => 'Magic Sarap All-in-One Seasoning 8g (Pack of 12)',
                'barcode' => $this->makeEan13('480000400008'),
                'cat' => 'Rice, Grains & Cooking Condiments',
                'sup' => 'Nestle Philippines Inc.',
                'price' => 48.00, 'cost' => 36.00, 'reorder' => 30, 'unit' => 'pack', 'stock' => 110,
            ],
            [
                'name' => 'Knorr Pork Broth Cubes 60g (6s)',
                'barcode' => $this->makeEan13('480000400009'),
                'cat' => 'Rice, Grains & Cooking Condiments',
                'sup' => 'Unilever Philippines Inc.',
                'price' => 42.00, 'cost' => 30.00, 'reorder' => 25, 'unit' => 'box', 'stock' => 95,
            ],

            // Canned Goods & Instant Meals
            [
                'name' => 'Century Tuna Flakes in Oil 155g',
                'barcode' => $this->makeEan13('480000500001'),
                'cat' => 'Canned Goods & Preserves',
                'sup' => 'Century Pacific Food Inc.',
                'price' => 42.50, 'cost' => 31.00, 'reorder' => 35, 'unit' => 'can', 'stock' => 150,
            ],
            [
                'name' => 'San Marino Corned Tuna 180g',
                'barcode' => $this->makeEan13('480000500002'),
                'cat' => 'Canned Goods & Preserves',
                'sup' => 'Century Pacific Food Inc.',
                'price' => 46.00, 'cost' => 34.00, 'reorder' => 30, 'unit' => 'can', 'stock' => 130,
            ],
            [
                'name' => 'Purefoods Corned Beef 150g',
                'barcode' => $this->makeEan13('480000500003'),
                'cat' => 'Canned Goods & Preserves',
                'sup' => 'San Miguel Food & Beverage Inc.',
                'price' => 72.00, 'cost' => 54.00, 'reorder' => 25, 'unit' => 'can', 'stock' => 110,
            ],
            [
                'name' => 'Mega Sardines in Tomato Sauce Red 155g',
                'barcode' => $this->makeEan13('480000500004'),
                'cat' => 'Canned Goods & Preserves',
                'sup' => 'Central Retail Distribution',
                'price' => 24.50, 'cost' => 17.50, 'reorder' => 40, 'unit' => 'can', 'stock' => 180,
            ],
            [
                'name' => '555 Carne Norte 150g',
                'barcode' => $this->makeEan13('480000500005'),
                'cat' => 'Canned Goods & Preserves',
                'sup' => 'Century Pacific Food Inc.',
                'price' => 28.00, 'cost' => 20.00, 'reorder' => 30, 'unit' => 'can', 'stock' => 100,
            ],
            [
                'name' => 'SPAM Classic Luncheon Meat 340g',
                'barcode' => $this->makeEan13('480000500006'),
                'cat' => 'Canned Goods & Preserves',
                'sup' => 'Central Retail Distribution',
                'price' => 195.00, 'cost' => 155.00, 'reorder' => 15, 'unit' => 'can', 'stock' => 60,
            ],
            [
                'name' => 'Del Monte Spaghetti Sauce Filipino Style 1kg',
                'barcode' => $this->makeEan13('480000500007'),
                'cat' => 'Canned Goods & Preserves',
                'sup' => 'Del Monte Philippines Inc.',
                'price' => 95.00, 'cost' => 72.00, 'reorder' => 20, 'unit' => 'pouch', 'stock' => 80,
            ],

            // Instant Noodles & Pastas
            [
                'name' => 'Lucky Me! Pancit Canton Kalamansi 80g',
                'barcode' => $this->makeEan13('480000600001'),
                'cat' => 'Noodles & Instant Meals',
                'sup' => 'Monde Nissin Corporation',
                'price' => 15.00, 'cost' => 10.50, 'reorder' => 50, 'unit' => 'pack', 'stock' => 250,
            ],
            [
                'name' => 'Lucky Me! Pancit Canton Chilimansi 80g',
                'barcode' => $this->makeEan13('480000600002'),
                'cat' => 'Noodles & Instant Meals',
                'sup' => 'Monde Nissin Corporation',
                'price' => 15.00, 'cost' => 10.50, 'reorder' => 50, 'unit' => 'pack', 'stock' => 220,
            ],
            [
                'name' => 'Lucky Me! Pancit Canton Extra Hot 80g',
                'barcode' => $this->makeEan13('480000600003'),
                'cat' => 'Noodles & Instant Meals',
                'sup' => 'Monde Nissin Corporation',
                'price' => 15.00, 'cost' => 10.50, 'reorder' => 40, 'unit' => 'pack', 'stock' => 180,
            ],
            [
                'name' => 'Lucky Me! Instant Mami Chicken 55g',
                'barcode' => $this->makeEan13('480000600004'),
                'cat' => 'Noodles & Instant Meals',
                'sup' => 'Monde Nissin Corporation',
                'price' => 12.50, 'cost' => 8.50, 'reorder' => 45, 'unit' => 'pack', 'stock' => 160,
            ],
            [
                'name' => 'Nissin Cup Noodles Seafood 40g',
                'barcode' => $this->makeEan13('480000600005'),
                'cat' => 'Noodles & Instant Meals',
                'sup' => 'Monde Nissin Corporation',
                'price' => 26.00, 'cost' => 18.00, 'reorder' => 30, 'unit' => 'cup', 'stock' => 120,
            ],
            [
                'name' => 'Del Monte Spaghetti Pasta 900g',
                'barcode' => $this->makeEan13('480000600006'),
                'cat' => 'Noodles & Instant Meals',
                'sup' => 'Del Monte Philippines Inc.',
                'price' => 88.00, 'cost' => 64.00, 'reorder' => 20, 'unit' => 'pack', 'stock' => 70,
            ],

            // Snacks & Biscuits
            [
                'name' => 'Piattos Cheese Flavored Potato Crisps 85g',
                'barcode' => $this->makeEan13('480000700001'),
                'cat' => 'Snacks, Chips & Biscuits',
                'sup' => 'Universal Robina Corporation (URC)',
                'price' => 38.00, 'cost' => 26.50, 'reorder' => 30, 'unit' => 'bag', 'stock' => 120,
            ],
            [
                'name' => 'Nova Multigrain Country Cheddar 78g',
                'barcode' => $this->makeEan13('480000700002'),
                'cat' => 'Snacks, Chips & Biscuits',
                'sup' => 'Universal Robina Corporation (URC)',
                'price' => 39.00, 'cost' => 27.50, 'reorder' => 25, 'unit' => 'bag', 'stock' => 95,
            ],
            [
                'name' => 'Oishi Prawn Crackers Spicy 60g',
                'barcode' => $this->makeEan13('480000700003'),
                'cat' => 'Snacks, Chips & Biscuits',
                'sup' => 'Liwayway Marketing Corporation (Oishi)',
                'price' => 22.50, 'cost' => 15.00, 'reorder' => 35, 'unit' => 'bag', 'stock' => 110,
            ],
            [
                'name' => 'Chippy Barbecue Flavor Corn Chips 110g',
                'barcode' => $this->makeEan13('480000700004'),
                'cat' => 'Snacks, Chips & Biscuits',
                'sup' => 'Universal Robina Corporation (URC)',
                'price' => 32.00, 'cost' => 22.00, 'reorder' => 30, 'unit' => 'bag', 'stock' => 100,
            ],
            [
                'name' => 'SkyFlakes Crackers (Pack of 10s)',
                'barcode' => $this->makeEan13('480000700005'),
                'cat' => 'Snacks, Chips & Biscuits',
                'sup' => 'Monde Nissin Corporation',
                'price' => 58.00, 'cost' => 42.00, 'reorder' => 25, 'unit' => 'pack', 'stock' => 85,
            ],
            [
                'name' => 'Fita Biscuits 300g Box',
                'barcode' => $this->makeEan13('480000700006'),
                'cat' => 'Snacks, Chips & Biscuits',
                'sup' => 'Monde Nissin Corporation',
                'price' => 65.00, 'cost' => 48.00, 'reorder' => 20, 'unit' => 'box', 'stock' => 75,
            ],
            [
                'name' => 'Choc-Nut Peanut Milk Chocolate (24s)',
                'barcode' => $this->makeEan13('480000700007'),
                'cat' => 'Snacks, Chips & Biscuits',
                'sup' => 'Central Retail Distribution',
                'price' => 48.00, 'cost' => 34.00, 'reorder' => 20, 'unit' => 'pack', 'stock' => 80,
            ],

            // Beverages, Juices & Coffee
            [
                'name' => 'Coca-Cola 1.5L PET Bottle',
                'barcode' => $this->makeEan13('480000800001'),
                'cat' => 'Beverages, Juices & Coffee',
                'sup' => 'Central Retail Distribution',
                'price' => 68.00, 'cost' => 50.00, 'reorder' => 30, 'unit' => 'bottle', 'stock' => 120,
            ],
            [
                'name' => 'Sprite 1.5L PET Bottle',
                'barcode' => $this->makeEan13('480000800002'),
                'cat' => 'Beverages, Juices & Coffee',
                'sup' => 'Central Retail Distribution',
                'price' => 68.00, 'cost' => 50.00, 'reorder' => 25, 'unit' => 'bottle', 'stock' => 90,
            ],
            [
                'name' => 'Royal Tru-Orange 1.5L PET Bottle',
                'barcode' => $this->makeEan13('480000800003'),
                'cat' => 'Beverages, Juices & Coffee',
                'sup' => 'Central Retail Distribution',
                'price' => 68.00, 'cost' => 50.00, 'reorder' => 25, 'unit' => 'bottle', 'stock' => 85,
            ],
            [
                'name' => 'C2 Green Tea Apple 500ml',
                'barcode' => $this->makeEan13('480000800004'),
                'cat' => 'Beverages, Juices & Coffee',
                'sup' => 'Universal Robina Corporation (URC)',
                'price' => 26.00, 'cost' => 18.00, 'reorder' => 40, 'unit' => 'bottle', 'stock' => 160,
            ],
            [
                'name' => 'Nescafe Classic Instant Coffee 100g Jar',
                'barcode' => $this->makeEan13('480000800005'),
                'cat' => 'Beverages, Juices & Coffee',
                'sup' => 'Nestle Philippines Inc.',
                'price' => 135.00, 'cost' => 102.00, 'reorder' => 20, 'unit' => 'jar', 'stock' => 75,
            ],
            [
                'name' => 'Kopiko Blanca Coffee Mix 30g (Pack of 10)',
                'barcode' => $this->makeEan13('480000800006'),
                'cat' => 'Beverages, Juices & Coffee',
                'sup' => 'Central Retail Distribution',
                'price' => 85.00, 'cost' => 64.00, 'reorder' => 30, 'unit' => 'pack', 'stock' => 110,
            ],
            [
                'name' => 'Milo Chocolate Malt Drink 1kg Pouch',
                'barcode' => $this->makeEan13('480000800007'),
                'cat' => 'Beverages, Juices & Coffee',
                'sup' => 'Nestle Philippines Inc.',
                'price' => 285.00, 'cost' => 228.00, 'reorder' => 15, 'unit' => 'pouch', 'stock' => 60,
            ],
            [
                'name' => 'San Miguel Pale Pilsen 330ml Can',
                'barcode' => $this->makeEan13('480000800008'),
                'cat' => 'Beer & Liquors',
                'sup' => 'San Miguel Food & Beverage Inc.',
                'price' => 52.00, 'cost' => 39.00, 'reorder' => 30, 'unit' => 'can', 'stock' => 140,
            ],
            [
                'name' => 'San Miguel Light 330ml Can',
                'barcode' => $this->makeEan13('480000800009'),
                'cat' => 'Beer & Liquors',
                'sup' => 'San Miguel Food & Beverage Inc.',
                'price' => 52.00, 'cost' => 39.00, 'reorder' => 30, 'unit' => 'can', 'stock' => 130,
            ],

            // Household & Cleaning
            [
                'name' => 'Joy Dishwashing Liquid Lemon 495ml Bottle',
                'barcode' => $this->makeEan13('480000900001'),
                'cat' => 'Household & Cleaning Supplies',
                'sup' => 'Procter & Gamble Philippines',
                'price' => 78.00, 'cost' => 58.00, 'reorder' => 25, 'unit' => 'bottle', 'stock' => 90,
            ],
            [
                'name' => 'Surf Sun Fresh Powder Detergent 1.1kg',
                'barcode' => $this->makeEan13('480000900002'),
                'cat' => 'Household & Cleaning Supplies',
                'sup' => 'Unilever Philippines Inc.',
                'price' => 115.00, 'cost' => 88.00, 'reorder' => 20, 'unit' => 'pouch', 'stock' => 75,
            ],
            [
                'name' => 'Ariel Sunrise Fresh Powder Detergent 1.3kg',
                'barcode' => $this->makeEan13('480000900003'),
                'cat' => 'Household & Cleaning Supplies',
                'sup' => 'Procter & Gamble Philippines',
                'price' => 175.00, 'cost' => 135.00, 'reorder' => 15, 'unit' => 'pouch', 'stock' => 60,
            ],
            [
                'name' => 'Downy Sunrise Fresh Fabric Conditioner 800ml',
                'barcode' => $this->makeEan13('480000900004'),
                'cat' => 'Household & Cleaning Supplies',
                'sup' => 'Procter & Gamble Philippines',
                'price' => 135.00, 'cost' => 102.00, 'reorder' => 20, 'unit' => 'pouch', 'stock' => 70,
            ],
            [
                'name' => 'Zonrox Bleach Original 1L',
                'barcode' => $this->makeEan13('480000900005'),
                'cat' => 'Household & Cleaning Supplies',
                'sup' => 'Central Retail Distribution',
                'price' => 48.00, 'cost' => 34.00, 'reorder' => 20, 'unit' => 'bottle', 'stock' => 80,
            ],

            // Personal Care & Baby
            [
                'name' => 'Safeguard Pure White Bar Soap 130g',
                'barcode' => $this->makeEan13('480001000001'),
                'cat' => 'Personal Care & Grooming',
                'sup' => 'Procter & Gamble Philippines',
                'price' => 46.00, 'cost' => 33.00, 'reorder' => 35, 'unit' => 'bar', 'stock' => 120,
            ],
            [
                'name' => 'Palmolive Naturals Shampoo 180ml',
                'barcode' => $this->makeEan13('480001000002'),
                'cat' => 'Personal Care & Grooming',
                'sup' => 'Central Retail Distribution',
                'price' => 112.00, 'cost' => 84.00, 'reorder' => 20, 'unit' => 'bottle', 'stock' => 65,
            ],
            [
                'name' => 'Head & Shoulders Cool Menthol Shampoo 170ml',
                'barcode' => $this->makeEan13('480001000003'),
                'cat' => 'Personal Care & Grooming',
                'sup' => 'Procter & Gamble Philippines',
                'price' => 148.00, 'cost' => 114.00, 'reorder' => 20, 'unit' => 'bottle', 'stock' => 60,
            ],
            [
                'name' => 'Colgate Total Clean Mint Toothpaste 150g',
                'barcode' => $this->makeEan13('480001000004'),
                'cat' => 'Personal Care & Grooming',
                'sup' => 'Central Retail Distribution',
                'price' => 135.00, 'cost' => 102.00, 'reorder' => 25, 'unit' => 'tube', 'stock' => 80,
            ],
            [
                'name' => 'Close Up Red Hot Gel Toothpaste 120g',
                'barcode' => $this->makeEan13('480001000005'),
                'cat' => 'Personal Care & Grooming',
                'sup' => 'Unilever Philippines Inc.',
                'price' => 89.00, 'cost' => 65.00, 'reorder' => 25, 'unit' => 'tube', 'stock' => 85,
            ],
            [
                'name' => 'Pampers Baby Dry Pants Medium (Pack of 40s)',
                'barcode' => $this->makeEan13('480001100001'),
                'cat' => 'Baby Care Essentials',
                'sup' => 'Procter & Gamble Philippines',
                'price' => 360.00, 'cost' => 285.00, 'reorder' => 15, 'unit' => 'pack', 'stock' => 45,
            ],
            [
                'name' => 'Johnsons Baby Powder Blossom 200g',
                'barcode' => $this->makeEan13('480001100002'),
                'cat' => 'Baby Care Essentials',
                'sup' => 'Central Retail Distribution',
                'price' => 95.00, 'cost' => 70.00, 'reorder' => 20, 'unit' => 'bottle', 'stock' => 70,
            ],

            // Breakfast & Bakery Essentials
            [
                'name' => 'Gardenia Classic White Bread 400g',
                'barcode' => $this->makeEan13('480001200001'),
                'cat' => 'Breakfast & Bakery',
                'sup' => 'Universal Robina Corporation (URC)',
                'price' => 65.00, 'cost' => 48.00, 'reorder' => 20, 'unit' => 'pack', 'stock' => 45,
            ],
            [
                'name' => 'Eden Sandwich Spread 220ml',
                'barcode' => $this->makeEan13('480001200002'),
                'cat' => 'Breakfast & Bakery',
                'sup' => 'Monde Nissin Corporation',
                'price' => 82.00, 'cost' => 60.00, 'reorder' => 15, 'unit' => 'pouch', 'stock' => 50,
            ],
            [
                'name' => 'Nestle Koko Krunch Cereal 170g',
                'barcode' => $this->makeEan13('480001200003'),
                'cat' => 'Breakfast & Bakery',
                'sup' => 'Nestle Philippines Inc.',
                'price' => 98.00, 'cost' => 72.00, 'reorder' => 15, 'unit' => 'box', 'stock' => 35,
            ],
        ];

        $unitMapping = [
            'bag' => 'pack',
            'sack' => 'pack',
            'pouch' => 'pack',
            'pack' => 'pack',
            'bottle' => 'piece',
            'can' => 'piece',
            'cup' => 'piece',
            'jar' => 'piece',
            'bar' => 'piece',
            'tube' => 'piece',
            'tetra' => 'piece',
            'pc' => 'piece',
            'piece' => 'piece',
            'box' => 'box',
            'kg' => 'kg',
            'g' => 'g',
            'ml' => 'ml',
            'l' => 'l',
        ];

        $productModels = [];
        foreach ($catalog as $item) {
            $catId = $categories[$item['cat']]->category_id;
            $supId = $suppliers[$item['sup']]->supplier_id;
            $uom = $unitMapping[$item['unit']] ?? 'piece';

            $product = Product::query()->updateOrCreate(
                ['barcode' => $item['barcode']],
                [
                    'category_id' => $catId,
                    'supplier_id' => $supId,
                    'product_name' => $item['name'],
                    'unit_price' => $item['price'],
                    'cost_price' => $item['cost'],
                    'reorder_level' => $item['reorder'],
                    'unit_of_measure' => $uom,
                    'critical_reorder_level' => max(3, (int) round($item['reorder'] * 0.4)),
                    'is_active' => true,
                ],
            );

            $inv = Inventory::query()->where('product_id', $product->product_id)->first();
            if ($inv) {
                $inv->update(['stock_quantity' => $item['stock']]);
            } else {
                Inventory::query()->create([
                    'product_id' => $product->product_id,
                    'stock_quantity' => $item['stock'],
                ]);
            }

            $productModels[] = $product;
        }

        // -------------------------------------------------------------
        // 4. Loyalty Customers
        // -------------------------------------------------------------
        $loyaltyCustomers = [
            [
                'first_name' => 'Maria Clara',
                'last_name' => 'Santos',
                'contact_number' => '09171234888',
                'email' => 'mariaclara.santos@email.ph',
                'address' => 'Unit 402 Tower B, Light Residences, Mandaluyong City',
                'loyalty_points' => 485,
                'total_purchases' => 48500.00,
                'date_of_birth' => '1988-06-12',
                'customer_status' => 'active',
            ],
            [
                'first_name' => 'Juan Carlos',
                'last_name' => 'Dela Cruz',
                'contact_number' => '09189876543',
                'email' => 'juancarlos.dc@email.ph',
                'address' => '142 Rizal St, Barangay San Vicente, Calasiao, Pangasinan',
                'loyalty_points' => 320,
                'total_purchases' => 32000.00,
                'date_of_birth' => '1992-11-30',
                'customer_status' => 'active',
            ],
            [
                'first_name' => 'Corazon',
                'last_name' => 'Aquino-Reyes',
                'contact_number' => '09205551212',
                'email' => 'cora.reyes@email.ph',
                'address' => '88 Mabini Ave, Dagupan City, Pangasinan',
                'loyalty_points' => 960,
                'total_purchases' => 96000.00,
                'date_of_birth' => '1965-01-25',
                'customer_status' => 'active',
            ],
            [
                'first_name' => 'Ferdinand',
                'last_name' => 'Garcia',
                'contact_number' => '09228889900',
                'email' => 'ferdie.garcia@email.ph',
                'address' => '54 MacArthur Highway, Urdaneta City, Pangasinan',
                'loyalty_points' => 140,
                'total_purchases' => 14000.00,
                'date_of_birth' => '1995-09-18',
                'customer_status' => 'active',
            ],
            [
                'first_name' => 'Lea',
                'last_name' => 'Salonga-Mendoza',
                'contact_number' => '09177778899',
                'email' => 'lea.mendoza@email.ph',
                'address' => '77 Grass Residences, Quezon City',
                'loyalty_points' => 1250,
                'total_purchases' => 125000.00,
                'date_of_birth' => '1982-03-22',
                'customer_status' => 'active',
            ],
        ];

        foreach ($loyaltyCustomers as $cust) {
            Customer::query()->updateOrCreate(
                ['contact_number' => $cust['contact_number']],
                $cust,
            );
        }

        // -------------------------------------------------------------
        // 5. Promotions & Store Discounts
        // -------------------------------------------------------------
        $discounts = [
            ['discount_name' => 'Senior Citizen 20%', 'discount_type' => 'percentage', 'discount_value' => 20, 'start_date' => now()->subYear(), 'end_date' => now()->addYears(2)],
            ['discount_name' => 'PWD Discount 20%', 'discount_type' => 'percentage', 'discount_value' => 20, 'start_date' => now()->subYear(), 'end_date' => now()->addYears(2)],
            ['discount_name' => 'Loyalty Member Special 5%', 'discount_type' => 'percentage', 'discount_value' => 5, 'start_date' => now()->subMonths(6), 'end_date' => now()->addYear()],
        ];

        foreach ($discounts as $d) {
            Discount::query()->firstOrCreate(
                ['discount_name' => $d['discount_name']],
                $d,
            );
        }

        // Auto-promotions
        Promotion::query()->updateOrCreate(
            ['name' => 'Super Weekend Sale 10% Off'],
            [
                'type' => 'percentage',
                'value' => 10.00,
                'scope' => 'cart',
                'scope_id' => null,
                'is_active' => true,
                'starts_at' => now()->subDays(5),
                'ends_at' => now()->addDays(30),
            ]
        );

        $householdCategory = $categories['Household & Cleaning Supplies'] ?? null;
        if ($householdCategory) {
            Promotion::query()->updateOrCreate(
                ['name' => 'Clean Home Essentials 15% Off'],
                [
                    'type' => 'percentage',
                    'value' => 15.00,
                    'scope' => 'category',
                    'scope_id' => $householdCategory->category_id,
                    'is_active' => true,
                    'starts_at' => now()->subDays(10),
                    'ends_at' => now()->addDays(20),
                ]
            );
        }

        // -------------------------------------------------------------
        // 6. Store Coupons
        // -------------------------------------------------------------
        $coupons = [
            [
                'code' => 'LOYALTY50',
                'description' => '₱50 off min ₱500 for loyalty cardholders',
                'type' => 'fixed',
                'value' => 50.00,
                'min_purchase' => 500.00,
                'max_uses' => 500,
                'per_customer_limit' => 2,
                'starts_at' => now()->subDays(15),
                'ends_at' => now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'VALUE100',
                'description' => '₱100 off min ₱1,000 on value essentials groceries',
                'type' => 'fixed',
                'value' => 100.00,
                'min_purchase' => 1000.00,
                'max_uses' => 250,
                'per_customer_limit' => 1,
                'starts_at' => now()->subDays(7),
                'ends_at' => now()->addMonths(2),
                'is_active' => true,
            ],
            [
                'code' => 'WEEKENDSALE',
                'description' => '10% discount during Super Weekend Sale',
                'type' => 'percentage',
                'value' => 10.00,
                'min_purchase' => 800.00,
                'max_uses' => 1000,
                'per_customer_limit' => 3,
                'starts_at' => now()->subDays(3),
                'ends_at' => now()->addDays(14),
                'is_active' => true,
            ],
            [
                'code' => 'SAVEBIG15',
                'description' => '15% off big grocery cart min ₱1,500',
                'type' => 'percentage',
                'value' => 15.00,
                'min_purchase' => 1500.00,
                'max_uses' => 300,
                'per_customer_limit' => 1,
                'starts_at' => now()->subDays(10),
                'ends_at' => now()->addMonth(),
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $c) {
            Coupon::query()->updateOrCreate(
                ['code' => $c['code']],
                $c,
            );
        }
    }
}
