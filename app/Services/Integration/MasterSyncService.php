<?php

namespace App\Services\Integration;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class MasterSyncService
{
    public function __construct(private DirectoryClient $client) {}

    public function sync(string $system): int
    {
        $paths = ['inventory' => '/api/v1/catalog/products', 'hrms' => config('services.hrms.employees_path', '/api/v1/employees'), 'crm' => '/api/v1/customers'];
        if (! isset($paths[$system])) throw new RuntimeException('Unknown integration system.');
        $rows = $this->client->rows($system, $paths[$system]);
        $key = ['inventory' => 'sku', 'hrms' => 'employee_no', 'crm' => 'customer_code'][$system];
        $identities = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! is_string($row[$key] ?? null) || isset($identities[$row[$key]])) {
                throw new RuntimeException("Invalid or duplicate $system identity.");
            }
            $identities[$row[$key]] = true;
            $this->validate($system, $row);
        }
        return DB::transaction(function () use ($system, $rows): int {
            foreach ($rows as $row) {
                match ($system) {
                    'inventory' => $this->product($row),
                    'hrms' => $this->employee($row),
                    'crm' => $this->customer($row),
                };
            }
            // Missing source records become inactive; historical POS keys remain.
            if ($system === 'inventory') {
                $missing = DB::table('inventory_product_mappings')->whereNotIn('inventory_sku', array_column($rows, 'sku'))->pluck('pos_product_id');
                Product::whereIn('product_id', $missing)->update(['is_active' => false]);
            } elseif ($system === 'hrms') {
                Employee::whereNotNull('hrms_employee_no')->whereNotIn('hrms_employee_no', array_column($rows, 'employee_no'))
                    ->update(['status' => 'inactive', 'hrms_employment_status' => 'inactive']);
            } else {
                Customer::whereNotNull('crm_external_id')->whereNotIn('crm_external_id', array_column($rows, 'external_id'))
                    ->update(['customer_status' => 'inactive', 'crm_discount_eligible' => false]);
            }
            return count($rows);
        });
    }

    private function validate(string $system, array $row): void
    {
        $rules = match ($system) {
            'inventory' => [
                'sku' => 'required|string|max:100', 'name' => 'required|string|max:150',
                'barcode' => 'nullable|string|max:50', 'status' => 'required|in:Active,Inactive',
                'unit_price' => 'required|numeric|min:0|max:99999999.99', 'cost_price' => 'required|numeric|min:0|max:99999999.99',
                'on_hand' => 'required|numeric|min:0|max:9999999.999', 'reorder_point' => 'required|numeric|min:0|max:2147483647',
            ],
            'hrms' => [
                'employee_no' => 'required|string|max:40|regex:/^[A-Za-z0-9._-]+$/',
                'first_name' => 'required|string|max:50', 'last_name' => 'required|string|max:50',
                'contact_number' => 'nullable|string|max:20', 'hire_date' => 'nullable|date', 'position' => 'nullable|string|max:150',
                'employment_status' => 'required|in:active,probation,on_leave,inactive,resigned',
            ],
            'crm' => [
                'customer_code' => 'required|string|max:100|regex:/^[A-Za-z0-9][A-Za-z0-9._:-]*$/',
                'first_name' => 'required|string|max:50', 'last_name' => 'required|string|max:50',
                'email' => 'nullable|email|max:100', 'phone' => 'nullable|string|max:20', 'status' => 'required|in:Active,Inactive',
                'loyalty.points' => 'required|integer|min:0', 'discount_eligibility.eligible' => 'required|boolean',
                'discount_eligibility.type' => 'nullable|in:Senior Citizen,PWD',
            ],
        };
        Validator::make($row, $rules)->validate();
        $expected = match ($system) {
            'inventory' => 'inventory:sku:'.$row['sku'], 'hrms' => 'hrms:employee:'.$row['employee_no'], 'crm' => 'crm:customer:'.$row['customer_code'],
        };
        if (($row['external_id'] ?? null) !== $expected) throw new RuntimeException('External identity does not match its source key.');
    }

    private function product(array $row): void
    {
        $mapping = DB::table('inventory_product_mappings')->where('inventory_sku', $row['sku'])->lockForUpdate()->first();
        $product = $mapping ? Product::whereKey($mapping->pos_product_id)->lockForUpdate()->firstOrFail() : new Product();
        $barcode = $row['barcode'] ?? ($product->exists ? $product->barcode : Product::generateBarcode());
        if (Product::where('barcode', $barcode)->when($product->exists, fn ($query) => $query->where('product_id', '<>', $product->product_id))->exists()) {
            throw new RuntimeException('Barcode conflict for Inventory SKU '.$row['sku'].'; explicit mapping is required.');
        }
        $product->fill([
            'product_name' => $row['name'], 'barcode' => $barcode, 'unit_price' => $row['unit_price'],
            'cost_price' => $row['cost_price'], 'reorder_level' => (int) ceil($row['reorder_point']),
            'is_active' => $row['status'] === 'Active',
        ]);
        if (! $product->exists) $product->unit_of_measure = 'piece';
        $product->save();
        if (! $mapping) DB::table('inventory_product_mappings')->insert([
            'pos_product_id' => $product->product_id, 'inventory_sku' => $row['sku'], 'created_at' => now(), 'updated_at' => now(),
        ]);
        $inventory = Inventory::where('product_id', $product->product_id)->lockForUpdate()->first();
        // Never replace a local deduction before its remote acknowledgement.
        if (DB::table('inventory_outbox_event')->where('product_id', $product->product_id)->whereNull('processed_at')->exists()) return;
        $reserved = 0.0;
        foreach (['pending_card_verifications', 'pending_ewallet_verifications'] as $table) {
            foreach (DB::table($table)->where('status', 'pending')->get() as $pending) {
                $payload = json_decode($pending->checkout_payload, true, 512, JSON_THROW_ON_ERROR);
                foreach (($payload['items'] ?? []) as $item) {
                    if ((int) ($item['product_id'] ?? 0) === (int) $product->product_id) $reserved += (float) $item['quantity'];
                }
            }
        }
        $inventory ??= new Inventory(['product_id' => $product->product_id]);
        $inventory->stock_quantity = max(0, round((float) $row['on_hand'] - $reserved, 3));
        $inventory->updated_at = now();
        $inventory->save();
    }

    private function employee(array $row): void
    {
        $employee = Employee::where('hrms_employee_no', $row['employee_no'])->lockForUpdate()->first();
        if (! $employee) {
            $username = 'hr_'.$row['employee_no'];
            if (Employee::where('username', $username)->exists()) throw new RuntimeException('HR username conflict requires explicit mapping.');
            $employee = new Employee([
                'username' => $username, 'password' => Hash::make(Str::random(64)),
                'role_id' => Role::where('role_name', 'Cashier')->firstOrFail()->role_id, 'status' => 'inactive',
            ]);
        }
        $employee->forceFill([
            'hrms_employee_no' => $row['employee_no'], 'hrms_employment_status' => $row['employment_status'],
            'hrms_position' => $row['position'] ?? null, 'hrms_synced_at' => now(),
            'first_name' => $row['first_name'], 'last_name' => $row['last_name'], 'contact_number' => $row['contact_number'] ?: null,
            'hire_date' => $row['hire_date'] ?? ($employee->hire_date ?? now()->toDateString()),
        ]);
        if (in_array($row['employment_status'], ['inactive', 'resigned'], true)) $employee->status = 'inactive';
        $employee->save();
    }

    private function customer(array $row): void
    {
        $customer = Customer::where('crm_external_id', $row['external_id'])->lockForUpdate()->first();
        if (filled($row['email'] ?? null) && Customer::where('email', $row['email'])
            ->when($customer, fn ($query) => $query->where('customer_id', '<>', $customer->customer_id))->exists()) {
            throw new RuntimeException('CRM email conflict requires explicit identity mapping.');
        }
        $customer ??= new Customer(['crm_external_id' => $row['external_id'], 'total_purchases' => 0]);
        $customer->fill([
            'first_name' => $row['first_name'], 'last_name' => $row['last_name'], 'email' => $row['email'] ?: null,
            'contact_number' => $row['phone'] ?: null, 'customer_status' => strtolower($row['status']),
            'loyalty_points' => $row['loyalty']['points'],
            'crm_discount_eligible' => $row['discount_eligibility']['eligible'],
            'crm_discount_type' => $row['discount_eligibility']['eligible'] ? $row['discount_eligibility']['type'] : null,
        ])->save();
    }
}
