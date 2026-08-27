<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\SaleDetail;
use App\Models\SaleTransaction;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class RealisticDataSeeder extends Seeder
{
    /**
     * Compute EAN-13 check digit for a 12-digit base string.
     */
    private function ean13CheckDigit(string $base12): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $base12[$i];
            $sum += $digit * (($i % 2 === 0) ? 1 : 3);
        }
        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Generate a valid EAN-13 barcode from a 12-digit base.
     */
    private function makeEan13(string $base12): string
    {
        return $base12 . $this->ean13CheckDigit($base12);
    }

    public function run(): void
    {
        // Categories
        $categories = [
            ['category_name' => 'Beverages', 'description' => 'Drinks, juices, coffee, tea'],
            ['category_name' => 'Snacks', 'description' => 'Chips, crackers, nuts, candy'],
            ['category_name' => 'Dairy', 'description' => 'Milk, cheese, yogurt, butter'],
            ['category_name' => 'Bakery', 'description' => 'Bread, pastries, cakes'],
            ['category_name' => 'Produce', 'description' => 'Fresh fruits and vegetables'],
            ['category_name' => 'Meat & Seafood', 'description' => 'Fresh and frozen meats, fish'],
            ['category_name' => 'Frozen Foods', 'description' => 'Frozen meals, ice cream'],
            ['category_name' => 'Household', 'description' => 'Cleaning supplies, paper goods'],
            ['category_name' => 'Personal Care', 'description' => 'Shampoo, soap, toothpaste'],
            ['category_name' => 'Canned Goods', 'description' => 'Canned vegetables, soups, beans'],
        ];

        $catModels = [];
        foreach ($categories as $cat) {
            $catModels[] = Category::firstOrCreate(
                ['category_name' => $cat['category_name']],
                ['description' => $cat['description']]
            );
        }

        // Suppliers
        $suppliers = [
            ['supplier_name' => 'Pacific Distributors', 'contact_number' => '555-0101', 'email' => 'orders@pacificdist.com', 'address' => '123 Commerce St, Manila'],
            ['supplier_name' => 'Fresh Valley Farms', 'contact_number' => '555-0102', 'email' => 'supply@freshvalley.com', 'address' => '456 Farm Rd, Cavite'],
            ['supplier_name' => 'Metro Wholesale', 'contact_number' => '555-0103', 'email' => 'bulk@metrowholesale.com', 'address' => '789 Industrial Ave, Makati'],
        ];

        $supModels = [];
        foreach ($suppliers as $sup) {
            $supModels[] = Supplier::firstOrCreate(
                ['supplier_name' => $sup['supplier_name']],
                $sup
            );
        }

        // Products with barcodes
        $products = [
            ['product_name' => 'Coca-Cola 500ml', 'barcode' => $this->makeEan13('890123456789'), 'unit_price' => 35.00, 'cost_price' => 22.00, 'reorder_level' => 24, 'category' => 0, 'supplier' => 0],
            ['product_name' => 'Pepsi 500ml', 'barcode' => $this->makeEan13('890123456790'), 'unit_price' => 35.00, 'cost_price' => 22.00, 'reorder_level' => 24, 'category' => 0, 'supplier' => 0],
            ['product_name' => 'Nestle Coffee 3in1 (10s)', 'barcode' => $this->makeEan13('890123456791'), 'unit_price' => 85.00, 'cost_price' => 62.00, 'reorder_level' => 12, 'category' => 0, 'supplier' => 2],
            ['product_name' => 'Lipton Yellow Label (25s)', 'barcode' => $this->makeEan13('890123456792'), 'unit_price' => 125.00, 'cost_price' => 95.00, 'reorder_level' => 10, 'category' => 0, 'supplier' => 2],
            ['product_name' => 'Lays Classic 100g', 'barcode' => $this->makeEan13('890123456793'), 'unit_price' => 45.00, 'cost_price' => 30.00, 'reorder_level' => 20, 'category' => 1, 'supplier' => 0],
            ['product_name' => 'Oishi Prawn Crackers 60g', 'barcode' => $this->makeEan13('890123456794'), 'unit_price' => 25.00, 'cost_price' => 15.00, 'reorder_level' => 30, 'category' => 1, 'supplier' => 0],
            ['product_name' => 'Snickers Bar', 'barcode' => $this->makeEan13('890123456795'), 'unit_price' => 55.00, 'cost_price' => 38.00, 'reorder_level' => 15, 'category' => 1, 'supplier' => 2],
            ['product_name' => 'Bear Brand Milk 1L', 'barcode' => $this->makeEan13('890123456796'), 'unit_price' => 78.00, 'cost_price' => 58.00, 'reorder_level' => 12, 'category' => 2, 'supplier' => 1],
            ['product_name' => 'Eden Cheese 160g', 'barcode' => $this->makeEan13('890123456797'), 'unit_price' => 92.00, 'cost_price' => 70.00, 'reorder_level' => 10, 'category' => 2, 'supplier' => 1],
            ['product_name' => 'Magnolia Fresh Milk 1L', 'barcode' => $this->makeEan13('890123456798'), 'unit_price' => 82.00, 'cost_price' => 60.00, 'reorder_level' => 12, 'category' => 2, 'supplier' => 1],
            ['product_name' => 'Gardenia Bread (Classic)', 'barcode' => $this->makeEan13('890123456799'), 'unit_price' => 62.00, 'cost_price' => 45.00, 'reorder_level' => 8, 'category' => 3, 'supplier' => 1],
            ['product_name' => 'Egg (per piece)', 'barcode' => $this->makeEan13('890123456800'), 'unit_price' => 12.00, 'cost_price' => 8.00, 'reorder_level' => 50, 'category' => 4, 'supplier' => 1],
            ['product_name' => 'Banana (per kg)', 'barcode' => $this->makeEan13('890123456801'), 'unit_price' => 55.00, 'cost_price' => 35.00, 'reorder_level' => 15, 'category' => 4, 'supplier' => 1],
            ['product_name' => 'Apple Fuji (per kg)', 'barcode' => $this->makeEan13('890123456802'), 'unit_price' => 120.00, 'cost_price' => 85.00, 'reorder_level' => 10, 'category' => 4, 'supplier' => 1],
            ['product_name' => 'Chicken Breast (per kg)', 'barcode' => $this->makeEan13('890123456803'), 'unit_price' => 185.00, 'cost_price' => 140.00, 'reorder_level' => 8, 'category' => 5, 'supplier' => 1],
            ['product_name' => 'Pork Belly (per kg)', 'barcode' => $this->makeEan13('890123456804'), 'unit_price' => 250.00, 'cost_price' => 195.00, 'reorder_level' => 6, 'category' => 5, 'supplier' => 1],
            ['product_name' => 'Hotdog (500g)', 'barcode' => $this->makeEan13('890123456805'), 'unit_price' => 95.00, 'cost_price' => 68.00, 'reorder_level' => 10, 'category' => 6, 'supplier' => 2],
            ['product_name' => 'Ice Cream (1L)', 'barcode' => $this->makeEan13('890123456806'), 'unit_price' => 145.00, 'cost_price' => 105.00, 'reorder_level' => 6, 'category' => 6, 'supplier' => 2],
            ['product_name' => 'Tide Powder 1kg', 'barcode' => $this->makeEan13('890123456807'), 'unit_price' => 115.00, 'cost_price' => 82.00, 'reorder_level' => 8, 'category' => 7, 'supplier' => 2],
            ['product_name' => 'Colgate Toothpaste 100ml', 'barcode' => $this->makeEan13('890123456808'), 'unit_price' => 68.00, 'cost_price' => 48.00, 'reorder_level' => 12, 'category' => 8, 'supplier' => 2],
            ['product_name' => 'Del Monte Tomato Sauce 250g', 'barcode' => $this->makeEan13('890123456809'), 'unit_price' => 32.00, 'cost_price' => 22.00, 'reorder_level' => 15, 'category' => 9, 'supplier' => 0],
            ['product_name' => 'Century Tuna 155g', 'barcode' => $this->makeEan13('890123456810'), 'unit_price' => 48.00, 'cost_price' => 33.00, 'reorder_level' => 20, 'category' => 9, 'supplier' => 0],
            ['product_name' => 'Lucky Me Pancit Canton', 'barcode' => $this->makeEan13('890123456811'), 'unit_price' => 14.00, 'cost_price' => 9.00, 'reorder_level' => 40, 'category' => 9, 'supplier' => 0],
            ['product_name' => 'Sprite 1.5L', 'barcode' => $this->makeEan13('890123456812'), 'unit_price' => 48.00, 'cost_price' => 32.00, 'reorder_level' => 12, 'category' => 0, 'supplier' => 0],
        ];

        $prodModels = [];
        foreach ($products as $prod) {
            $p = Product::updateOrCreate(
                ['barcode' => $prod['barcode']],
                [
                    'product_name' => $prod['product_name'],
                    'unit_price' => $prod['unit_price'],
                    'cost_price' => $prod['cost_price'],
                    'reorder_level' => $prod['reorder_level'],
                    'category_id' => $catModels[$prod['category']]->category_id,
                    'supplier_id' => $supModels[$prod['supplier']]->supplier_id,
                ]
            );
            Inventory::firstOrCreate(
                ['product_id' => $p->product_id],
                ['stock_quantity' => rand(5, 60)]
            );
            $prodModels[] = $p;
        }

        // Customers
        $customers = [
            ['first_name' => 'Maria', 'last_name' => 'Santos', 'contact_number' => '09171234567', 'email' => 'maria.santos@email.com', 'customer_status' => 'active'],
            ['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'contact_number' => '09181234567', 'email' => 'juan.dc@email.com', 'customer_status' => 'active'],
            ['first_name' => 'Ana', 'last_name' => 'Reyes', 'contact_number' => '09191234567', 'email' => 'ana.reyes@email.com', 'customer_status' => 'active'],
            ['first_name' => 'Pedro', 'last_name' => 'Garcia', 'contact_number' => '09201234567', 'email' => '', 'customer_status' => 'active'],
            ['first_name' => 'Rosa', 'last_name' => 'Mendoza', 'contact_number' => '09211234567', 'email' => 'rosa.m@email.com', 'customer_status' => 'active'],
            ['first_name' => 'Luis', 'last_name' => 'Torres', 'contact_number' => '09221234567', 'email' => '', 'customer_status' => 'inactive'],
        ];

        $custModels = [];
        foreach ($customers as $cust) {
            $custModels[] = Customer::firstOrCreate(
                ['contact_number' => $cust['contact_number']],
                $cust
            );
        }

        // Discounts
        $discounts = [
            ['discount_name' => 'Senior Citizen 20%', 'discount_type' => 'percentage', 'discount_value' => 20, 'start_date' => now()->subMonth(), 'end_date' => now()->addMonths(6)],
            ['discount_name' => 'PWD 15%', 'discount_type' => 'percentage', 'discount_value' => 15, 'start_date' => now()->subMonth(), 'end_date' => now()->addMonths(6)],
            ['discount_name' => 'Holiday Sale 50 off', 'discount_type' => 'fixed', 'discount_value' => 50, 'start_date' => now()->subWeek(), 'end_date' => now()->addWeek()],
        ];

        $discModels = [];
        foreach ($discounts as $disc) {
            $discModels[] = Discount::firstOrCreate(
                ['discount_name' => $disc['discount_name']],
                $disc
            );
        }

        // Generate realistic sales for last 30 days
        $cashier = Employee::where('username', 'cashier')->first();
        $admin = Employee::where('username', 'CARDENAS')->first();
        $manager = Employee::where('username', 'manager')->first();
        $cashiers = collect([$cashier, $admin, $manager])->filter();

        $paymentMethods = ['cash', 'card', 'e-wallet'];

        for ($day = 30; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $salesCount = rand(3, 12);

            for ($s = 0; $s < $salesCount; $s++) {
                $employee = $cashiers->random();
                $customer = rand(1, 10) > 3 ? $custModels[array_rand($custModels)] : null;
                $discount = rand(1, 10) > 7 ? $discModels[array_rand($discModels)] : null;
                $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

                // 1-5 items per sale
                $lineCount = rand(1, 5);
                $selectedProducts = collect($prodModels)->random($lineCount);

                $subtotal = 0;
                $lines = [];

                foreach ($selectedProducts as $product) {
                    $qty = rand(1, 4);
                    $lineTotal = round($product->unit_price * $qty, 2);
                    $subtotal += $lineTotal;
                    $lines[] = [
                        'product_id' => $product->product_id,
                        'quantity' => $qty,
                        'unit_price' => $product->unit_price,
                        'subtotal' => $lineTotal,
                    ];
                }

                $total = $discount ? $discount->applyTo($subtotal) : $subtotal;
                $amountPaid = $paymentMethod === 'cash' ? ceil($total / 50) * 50 : $total;
                $change = round($amountPaid - $total, 2);

                $sale = SaleTransaction::create([
                    'customer_id' => $customer?->customer_id,
                    'employee_id' => $employee->employee_id,
                    'discount_id' => $discount?->discount_id,
                    'transaction_date' => $date->copy()->addHours(rand(8, 20))->addMinutes(rand(0, 59)),
                    'subtotal' => $subtotal,
                    'total_amount' => $total,
                    'payment_method' => $paymentMethod,
                ]);

                foreach ($lines as $line) {
                    $sale->saleDetails()->create($line);

                    $inv = Inventory::firstOrCreate(
                        ['product_id' => $line['product_id']],
                        ['stock_quantity' => 50]
                    );
                    $inv->stock_quantity = max(0, $inv->stock_quantity - $line['quantity']);
                    $inv->save();
                }

                $sale->payment()->create([
                    'payment_method' => $paymentMethod,
                    'amount_paid' => $amountPaid,
                    'change_amount' => $change,
                    'payment_date' => $sale->transaction_date,
                ]);

                $sale->receipt()->create([
                    'receipt_number' => 'R' . $date->format('Ymd') . '-' . str_pad((string) $sale->transaction_id, 6, '0', STR_PAD_LEFT),
                    'issued_date' => $sale->transaction_date,
                ]);

                // Update customer stats
                if ($customer) {
                    $customer->total_purchases = (float) $customer->total_purchases + $total;
                    $customer->loyalty_points = (int) $customer->loyalty_points + (int) floor($total / 100);
                    $customer->save();
                }
            }
        }

        // Audit logs
        $actions = ['create', 'update', 'sale', 'delete'];
        $tables = ['product', 'category', 'customer', 'sale_transaction', 'employee'];
        for ($i = 0; $i < 50; $i++) {
            $emp = $cashiers->random();
            \App\Models\AuditLog::create([
                'employee_id' => $emp->employee_id,
                'action' => $actions[array_rand($actions)],
                'table_affected' => $tables[array_rand($tables)],
                'record_id' => rand(1, 20),
                'action_timestamp' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                'description' => 'System activity log entry #' . ($i + 1),
            ]);
        }

        // Purchase orders
        for ($i = 0; $i < 5; $i++) {
            $po = PurchaseOrder::create([
                'supplier_id' => $supModels[array_rand($supModels)]->supplier_id,
                'employee_id' => $employee->employee_id ?? $cashiers->first()->employee_id,
                'order_date' => now()->subDays(rand(1, 20)),
                'status' => $i < 3 ? 'received' : 'pending',
                'total_amount' => 0,
            ]);

            $poTotal = 0;
            $poProducts = collect($prodModels)->random(rand(2, 5));
            foreach ($poProducts as $product) {
                $qty = rand(10, 50);
                $lineTotal = round($product->cost_price * $qty, 2);
                $poTotal += $lineTotal;
                $po->details()->create([
                    'product_id' => $product->product_id,
                    'quantity' => $qty,
                    'unit_cost' => $product->cost_price,
                ]);
            }
            $po->update(['total_amount' => $poTotal]);
        }
    }
}
