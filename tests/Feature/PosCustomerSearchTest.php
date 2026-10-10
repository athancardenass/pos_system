<?php

namespace Tests\Feature;

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

    public function test_pos_customer_search_returns_empty_until_crm_integration_is_configured(): void
    {
        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($cashier)
            ->getJson(route('pos.customers.search', ['q' => 'Maria']))
            ->assertOk()
            ->assertExactJson(['customers' => []]);
    }

    public function test_customer_directory_is_manager_edit_only_while_preparing_crm_integration(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('customers.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('customers.edit'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('customers.update'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('customers.create'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('customers.store'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('customers.destroy'));
    }

    public function test_pos_register_does_not_embed_the_customer_directory(): void
    {
        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Search ID, name, phone, or email')
            ->assertDontSee('customersData');
    }
}
