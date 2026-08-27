<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Seed the suppliers used by the demo catalog.
     */
    public function run(): void
    {
        $suppliers = [
            ['supplier_name' => 'Metro Wholesale', 'contact_number' => '02-8123-4567', 'address' => 'Manila', 'email' => 'orders@metrowholesale.example'],
            ['supplier_name' => 'Prime Distributors', 'contact_number' => '02-8765-4321', 'address' => 'Quezon City', 'email' => 'sales@primedist.example'],
            ['supplier_name' => 'FreshLink Trading', 'contact_number' => '02-8345-6789', 'address' => 'Mandaluyong', 'email' => 'info@freshlink.example'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::query()->firstOrCreate(
                ['supplier_name' => $supplier['supplier_name']],
                [
                    'contact_number' => $supplier['contact_number'],
                    'address' => $supplier['address'],
                    'email' => $supplier['email'],
                ],
            );
        }
    }
}
