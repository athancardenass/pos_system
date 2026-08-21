<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'username' => 'admin',
                'role_name' => 'Admin',
                'first_name' => 'System',
                'last_name' => 'Admin',
            ],
            [
                'username' => 'manager',
                'role_name' => 'Manager',
                'first_name' => 'Store',
                'last_name' => 'Manager',
            ],
            [
                'username' => 'cashier',
                'role_name' => 'Cashier',
                'first_name' => 'Front',
                'last_name' => 'Cashier',
            ],
        ];

        foreach ($accounts as $account) {
            $role = Role::query()->where('role_name', $account['role_name'])->firstOrFail();

            Employee::query()->updateOrCreate(
                ['username' => $account['username']],
                [
                    'role_id' => $role->role_id,
                    'first_name' => $account['first_name'],
                    'last_name' => $account['last_name'],
                    'password' => 'password',
                    'contact_number' => null,
                    'hire_date' => now()->toDateString(),
                    'status' => 'active',
                ],
            );
        }
    }
}
