<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Admin', 'Manager', 'Cashier'] as $roleName) {
            Role::query()->firstOrCreate(
                ['role_name' => $roleName],
            );
        }
    }
}
