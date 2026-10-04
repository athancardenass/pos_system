<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCustomerSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_pos_customer_search_matches_customer_id_name_contact_and_email(): void
    {
        $customer = Customer::query()->create([
            'first_name' => 'Maria Clara',
            'last_name' => 'Santos Reyes',
            'contact_number' => '09171234567',
            'email' => 'maria.santos@example.test',
            'customer_status' => 'active',
        ]);

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        foreach (['Santos Reyes', (string) $customer->customer_id, '0917123', 'santos@example.test'] as $term) {
            $this->actingAs($cashier)
                ->getJson(route('pos.customers.search', ['q' => $term]))
                ->assertOk()
                ->assertJsonFragment([
                    'id' => $customer->customer_id,
                    'name' => 'Maria Clara Santos Reyes',
                    'contact' => '09171234567',
                    'points' => 0,
                ]);
        }
    }

    public function test_pos_customer_search_only_returns_active_matches_and_limits_results(): void
    {
        Customer::query()->create([
            'first_name' => 'Active',
            'last_name' => 'Customer',
            'customer_status' => 'active',
        ]);
        Customer::query()->create([
            'first_name' => 'Inactive',
            'last_name' => 'Customer',
            'customer_status' => 'inactive',
        ]);

        for ($index = 0; $index < 25; $index++) {
            Customer::query()->create([
                'first_name' => 'Bulk',
                'last_name' => 'Customer '.$index,
                'customer_status' => 'active',
            ]);
        }

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $response = $this->actingAs($cashier)
            ->getJson(route('pos.customers.search', ['q' => 'Customer']));

        $response->assertOk()->assertJsonCount(20, 'customers');
        $this->assertNotContains(
            'Inactive Customer',
            array_column($response->json('customers'), 'name'),
        );
    }

    public function test_pos_register_does_not_embed_the_customer_directory(): void
    {
        Customer::query()->create([
            'first_name' => 'Private',
            'last_name' => 'Directory Entry',
            'email' => 'directory-entry@example.test',
            'customer_status' => 'active',
        ]);

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Search ID, name, phone, or email')
            ->assertDontSee('directory-entry@example.test')
            ->assertDontSee('customersData');
    }
}
