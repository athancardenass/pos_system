<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the product categories used by the demo catalog.
     */
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Beverages', 'description' => 'Drinks, juices, and bottled water'],
            ['category_name' => 'Snacks', 'description' => 'Chips, biscuits, and confectionery'],
            ['category_name' => 'Personal Care', 'description' => 'Soap, shampoo, and hygiene items'],
            ['category_name' => 'Household', 'description' => 'Cleaning and home supplies'],
            ['category_name' => 'Stationery', 'description' => 'Papers, pens, and school supplies'],
        ];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate(
                ['category_name' => $category['category_name']],
                ['description' => $category['description']],
            );
        }
    }
}
