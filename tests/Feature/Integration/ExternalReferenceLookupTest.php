<?php

namespace Tests\Feature\Integration;

use App\Models\Employee;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalReferenceLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Config::set('services.hrms.base_url', 'https://hrms.example.test');
        Config::set('services.hrms.token', 'test-hrms-token');
        Config::set('services.inventory.base_url', 'https://inventory.example.test');
        Config::set('services.inventory.token', 'test-inventory-token');
        Http::preventStrayRequests();
    }

    public function test_manager_can_read_one_hrms_employee_by_exact_employee_number(): void
    {
        Http::fake([
            'https://hrms.example.test/api/v1/employees*' => Http::response([
                'data' => [[
                    'external_id' => 'hrms:employee:HR-0042',
                    'employee_no' => 'HR-0042',
                    'display_name' => 'Mila Cruz',
                    'employment_status' => 'active',
                    'position' => 'Cashier',
                    'mobile' => 'must not be exposed',
                ]],
                'meta' => ['page' => 1, 'per_page' => 2, 'total' => 1, 'last_page' => 1],
            ]),
        ]);

        $manager = Employee::query()->where('username', 'manager')->firstOrFail();

        $this->actingAs($manager)
            ->getJson('/pos/integrations/hrms/employee?employee_no=HR-0042')
            ->assertOk()
            ->assertExactJson([
                'employee' => [
                    'external_id' => 'hrms:employee:HR-0042',
                    'employee_no' => 'HR-0042',
                    'display_name' => 'Mila Cruz',
                    'employment_status' => 'active',
                    'position' => 'Cashier',
                ],
                'meta' => ['total' => 1],
            ]);

        Http::assertSent(static function (HttpRequest $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->hasHeader('Authorization', 'Bearer test-hrms-token')
                && $request->method() === 'GET'
                && parse_url($request->url(), PHP_URL_SCHEME) === 'https'
                && parse_url($request->url(), PHP_URL_PATH) === '/api/v1/employees'
                && ($query['employee_no'] ?? null) === 'HR-0042'
                && ($query['per_page'] ?? null) === '2';
        });
    }

    public function test_cashier_cannot_read_hrms_employee_directory(): void
    {
        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($cashier)
            ->getJson('/pos/integrations/hrms/employee?employee_no=HR-0042')
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_cashier_can_read_inventory_product_by_exact_sku_without_pos_mapping(): void
    {
        Http::fake([
            'https://inventory.example.test/api/v1/catalog/products*' => Http::response([
                'data' => [[
                    'external_id' => 'inventory:sku:SKU-0042',
                    'sku' => 'SKU-0042',
                    'barcode' => '480000000042',
                    'name' => 'Rice',
                    'shelf_qty' => 3,
                    'backroom_qty' => 7,
                    'on_hand' => 10,
                    'status' => 'Active',
                ]],
                'meta' => ['page' => 1, 'per_page' => 2, 'total' => 1, 'last_page' => 1],
            ]),
        ]);

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($cashier)
            ->getJson('/pos/integrations/inventory/product?sku=SKU-0042')
            ->assertOk()
            ->assertExactJson([
                'product' => [
                    'external_id' => 'inventory:sku:SKU-0042',
                    'sku' => 'SKU-0042',
                    'barcode' => '480000000042',
                    'name' => 'Rice',
                    'shelf_qty' => 3,
                    'backroom_qty' => 7,
                    'on_hand' => 10,
                    'status' => 'Active',
                ],
                'meta' => ['total' => 1],
            ]);

        Http::assertSent(static function (HttpRequest $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->hasHeader('Authorization', 'Bearer test-inventory-token')
                && $request->method() === 'GET'
                && parse_url($request->url(), PHP_URL_SCHEME) === 'https'
                && parse_url($request->url(), PHP_URL_PATH) === '/api/v1/catalog/products'
                && ($query['sku'] ?? null) === 'SKU-0042'
                && ($query['per_page'] ?? null) === '2';
        });
    }

    public function test_upstream_fuzzy_result_is_rejected_instead_of_mapped(): void
    {
        Http::fake([
            'https://inventory.example.test/api/v1/catalog/products*' => Http::response([
                'data' => [[
                    'external_id' => 'inventory:sku:SKU-00420',
                    'sku' => 'SKU-00420',
                    'barcode' => null,
                    'name' => 'Similar SKU',
                    'shelf_qty' => 0,
                    'backroom_qty' => 0,
                    'on_hand' => 0,
                    'status' => 'Active',
                ]],
                'meta' => ['page' => 1, 'per_page' => 2, 'total' => 1, 'last_page' => 1],
            ]),
        ]);

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($cashier)
            ->getJson('/pos/integrations/inventory/product?sku=SKU-0042')
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'inventory_unavailable');
    }

    public function test_inventory_exact_no_match_returns_an_empty_reference(): void
    {
        Http::fake([
            'https://inventory.example.test/api/v1/catalog/products*' => Http::response([
                'data' => [],
                'meta' => ['page' => 1, 'per_page' => 2, 'total' => 0, 'last_page' => 1],
            ]),
        ]);

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($cashier)
            ->getJson('/pos/integrations/inventory/product?sku=SKU-NOT-FOUND')
            ->assertOk()
            ->assertExactJson(['product' => null, 'meta' => ['total' => 0]]);
    }

    public function test_unconfigured_or_plain_http_receiver_is_rejected_without_network_access(): void
    {
        Config::set('services.hrms.base_url', 'http://hrms.example.test');
        Config::set('services.inventory.base_url', '');

        $manager = Employee::query()->where('username', 'manager')->firstOrFail();
        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($manager)
            ->getJson('/pos/integrations/hrms/employee?employee_no=HR-0042')
            ->assertServiceUnavailable();
        $this->actingAs($cashier)
            ->getJson('/pos/integrations/inventory/product?sku=SKU-0042')
            ->assertServiceUnavailable();

        Http::assertNothingSent();
    }

    public function test_receiver_timeout_returns_safe_gateway_timeout(): void
    {
        Http::fake(static fn () => throw new ConnectionException('private upstream detail'));

        $manager = Employee::query()->where('username', 'manager')->firstOrFail();

        $this->actingAs($manager)
            ->getJson('/pos/integrations/hrms/employee?employee_no=HR-0042')
            ->assertStatus(504)
            ->assertJsonPath('error.code', 'hrms_timeout')
            ->assertDontSee('private upstream detail');
    }

    public function test_invalid_receiver_tokens_fail_closed_without_exposing_upstream_body(): void
    {
        Http::fake([
            'https://hrms.example.test/api/v1/employees*' => Http::response(['message' => 'secret upstream body'], 401),
            'https://inventory.example.test/api/v1/catalog/products*' => Http::response(['message' => 'secret upstream body'], 401),
        ]);

        $manager = Employee::query()->where('username', 'manager')->firstOrFail();
        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($manager)
            ->getJson('/pos/integrations/hrms/employee?employee_no=HR-0042')
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'hrms_unavailable')
            ->assertDontSee('secret upstream body');
        $this->actingAs($cashier)
            ->getJson('/pos/integrations/inventory/product?sku=SKU-0042')
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'inventory_unavailable')
            ->assertDontSee('secret upstream body');
    }

    public function test_external_identifier_inputs_are_required_and_validated(): void
    {
        $manager = Employee::query()->where('username', 'manager')->firstOrFail();
        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($manager)
            ->getJson('/pos/integrations/hrms/employee')
            ->assertUnprocessable();
        $this->actingAs($cashier)
            ->getJson('/pos/integrations/inventory/product?sku=')
            ->assertUnprocessable();

        Http::assertNothingSent();
    }
}
