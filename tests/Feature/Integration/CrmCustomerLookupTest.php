<?php

namespace Tests\Feature\Integration;

use App\Models\Employee;
use App\Models\Customer;
use App\Models\SaleTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CrmCustomerLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Config::set('services.crm.base_url', 'https://crm.example.test');
        Config::set('services.crm.token', 'test-crm-token');
        Http::preventStrayRequests();
    }

    public function test_cashier_can_search_crm_and_receives_only_display_fields(): void
    {
        Http::fake([
            'https://crm.example.test/api/v1/customers*' => Http::response([
                'data' => [[
                    'external_id' => 'crm:customer:CUS-0042',
                    'customer_code' => 'CUS-0042',
                    'display_name' => 'Ana Santos',
                    'email' => 'ana@example.test',
                    'phone' => '09170000001',
                    'status' => 'Active',
                    'loyalty' => [
                        'points' => 520,
                        'membership_type' => 'Regular',
                        'eligible' => true,
                        'label' => 'Loyal Customer',
                        'remaining' => 0,
                    ],
                    'discount_eligibility' => ['eligible' => true, 'type' => 'PWD'],
                ]],
                'meta' => ['page' => 1, 'per_page' => 10, 'total' => 1, 'last_page' => 1],
            ]),
        ]);

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($cashier)
            ->getJson('/pos/customers/crm-search?q=Ana')
            ->assertOk()
            ->assertExactJson([
                'customers' => [[
                    'external_id' => 'crm:customer:CUS-0042',
                    'customer_code' => 'CUS-0042',
                    'display_name' => 'Ana Santos',
                    'status' => 'Active',
                    'loyalty' => [
                        'points' => 520,
                        'membership_type' => 'Regular',
                        'eligible' => true,
                        'label' => 'Loyal Customer',
                        'remaining' => 0,
                    ],
                    'discount_eligibility' => ['eligible' => true, 'type' => 'PWD'],
                ]],
                'meta' => ['total' => 1],
            ]);

        Http::assertSent(static function (HttpRequest $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->hasHeader('Authorization', 'Bearer test-crm-token')
                && parse_url($request->url(), PHP_URL_SCHEME) === 'https'
                && parse_url($request->url(), PHP_URL_PATH) === '/api/v1/customers'
                && ($query['search'] ?? null) === 'Ana'
                && ($query['status'] ?? null) === 'Active'
                && ($query['per_page'] ?? null) === '10';
        });

        $customersBefore = Customer::count();
        $this->getJson('/pos/customers/search?q=Ana')->assertOk()->assertJsonPath('customers.0', [
            'id' => null,
            'external_id' => 'crm:customer:CUS-0042',
            'name' => 'Ana Santos',
            'contact' => null,
            'points' => 520,
        ]);
        $this->assertSame($customersBefore, Customer::count());
    }

    public function test_manager_can_use_the_authenticated_crm_lookup(): void
    {
        Http::fake([
            'https://crm.example.test/api/v1/customers*' => Http::response([
                'data' => [],
                'meta' => ['page' => 1, 'per_page' => 10, 'total' => 0, 'last_page' => 1],
            ]),
        ]);

        $manager = Employee::query()->where('username', 'manager')->firstOrFail();

        $this->actingAs($manager)
            ->getJson('/pos/customers/crm-search?q=Ana')
            ->assertOk()
            ->assertExactJson(['customers' => [], 'meta' => ['total' => 0]]);
    }

    public function test_guest_cannot_use_the_crm_lookup(): void
    {
        $this->getJson('/pos/customers/crm-search?q=Ana')->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_empty_crm_search_returns_an_empty_collection_and_zero_total(): void
    {
        Http::fake([
            'https://crm.example.test/api/v1/customers*' => Http::response([
                'data' => [],
                'meta' => ['page' => 1, 'per_page' => 10, 'total' => 0, 'last_page' => 1],
            ]),
        ]);

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($cashier)
            ->getJson('/pos/customers/crm-search?q=no-match-20261010')
            ->assertOk()
            ->assertExactJson(['customers' => [], 'meta' => ['total' => 0]]);
    }

    public function test_unconfigured_crm_lookup_returns_a_safe_503(): void
    {
        Config::set('services.crm.base_url', '');
        Config::set('services.crm.token', '');

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($cashier)
            ->getJson('/pos/customers/crm-search?q=Ana')
            ->assertServiceUnavailable()
            ->assertExactJson(['error' => ['code' => 'crm_not_configured', 'message' => 'CRM customer lookup is not configured.']]);

        Http::assertNothingSent();
    }

    public function test_crm_authentication_or_response_failure_returns_a_safe_502(): void
    {
        Http::fake([
            'https://crm.example.test/api/v1/customers*' => Http::response([
                'error' => ['code' => 'unauthorized', 'message' => 'upstream detail must not be exposed'],
            ], 401),
        ]);

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($cashier)
            ->getJson('/pos/customers/crm-search?q=Ana')
            ->assertStatus(502)
            ->assertExactJson(['error' => ['code' => 'crm_unavailable', 'message' => 'CRM customer lookup is unavailable.']])
            ->assertDontSee('upstream detail must not be exposed');
    }

    public function test_crm_timeout_returns_a_safe_504(): void
    {
        Http::fake(static fn () => throw new ConnectionException('upstream timeout detail must not be exposed'));

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $this->actingAs($cashier)
            ->getJson('/pos/customers/crm-search?q=Ana')
            ->assertStatus(504)
            ->assertExactJson(['error' => ['code' => 'crm_timeout', 'message' => 'CRM customer lookup timed out.']])
            ->assertDontSee('upstream timeout detail must not be exposed');
    }

    public function test_register_keeps_crm_results_separate_from_the_checkout_customer_field(): void
    {
        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();

        $response = $this->actingAs($cashier)
            ->get('/pos')
            ->assertOk();

        $html = $response->getContent();
        self::assertTrue(str_contains($html, 'CRM customer lookup (read-only)'));
        self::assertTrue(str_contains($html, 'Use this CRM customer for checkout'));
        self::assertTrue(str_contains($html, 'name="customer_id" id="store-customer-id"'));
    }

    public function test_crm_external_id_is_rejected_by_the_integer_checkout_customer_field(): void
    {
        Http::fake([
            'https://crm.example.test/api/v1/customers*' => Http::response([
                'data' => [[
                    'external_id' => 'crm:customer:CUS-0042',
                    'customer_code' => 'CUS-0042',
                    'display_name' => 'Ana Santos',
                    'email' => 'ana@example.test',
                    'phone' => '09170000001',
                    'status' => 'Active',
                    'loyalty' => [
                        'points' => 520,
                        'membership_type' => 'Regular',
                        'eligible' => true,
                        'label' => 'Loyal Customer',
                        'remaining' => 0,
                    ],
                    'discount_eligibility' => ['eligible' => true, 'type' => 'PWD'],
                ]],
                'meta' => ['page' => 1, 'per_page' => 10, 'total' => 1, 'last_page' => 1],
            ]),
        ]);

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();
        $salesBefore = SaleTransaction::query()->count();
        $lookup = $this->actingAs($cashier)->getJson('/pos/customers/crm-search?q=Ana')->assertOk();
        $externalId = $lookup->json('customers.0.external_id');

        $this->actingAs($cashier)
            ->post('/pos', ['customer_id' => $externalId])
            ->assertSessionHasErrors('customer_id');

        $this->assertSame($salesBefore, SaleTransaction::query()->count());
    }

    public function test_crm_customer_can_be_synchronized_by_exact_canonical_id_without_using_it_as_the_pos_id(): void
    {
        Http::fake([
            'https://crm.example.test/api/v1/customers*' => Http::response([
                'data' => [[
                    'external_id' => 'crm:customer:CRM-0042',
                    'customer_code' => 'CRM-0042',
                    'first_name' => 'Ana',
                    'last_name' => 'Santos',
                    'display_name' => 'Ana Santos',
                    'email' => 'ana@example.test',
                    'phone' => '09170000001',
                    'status' => 'Active',
                    'discount_eligibility' => ['eligible' => true, 'type' => 'PWD'],
                ]],
                'meta' => ['page' => 1, 'per_page' => 2, 'total' => 1, 'last_page' => 1],
            ]),
        ]);

        $cashier = Employee::query()->where('username', 'cashier')->firstOrFail();
        $first = $this->actingAs($cashier)->postJson('/pos/customers/crm-sync', [
            'external_id' => 'crm:customer:CRM-0042',
        ])->assertCreated()->assertJsonPath('customer.external_id', 'crm:customer:CRM-0042');

        $posCustomerId = $first->json('customer.customer_id');
        $this->assertIsInt($posCustomerId);
        $this->assertSame('crm:customer:CRM-0042', Customer::query()->findOrFail($posCustomerId)->crm_external_id);
        $this->assertSame('PWD', Customer::query()->findOrFail($posCustomerId)->crm_discount_type);

        $second = $this->actingAs($cashier)->postJson('/pos/customers/crm-sync', [
            'external_id' => 'crm:customer:CRM-0042',
        ])->assertOk();

        $this->assertSame($posCustomerId, $second->json('customer.customer_id'));
        $this->assertSame(1, Customer::query()->where('crm_external_id', 'crm:customer:CRM-0042')->count());
        Http::assertSentCount(2);
        Http::assertSent(static function (HttpRequest $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->hasHeader('Authorization', 'Bearer test-crm-token')
                && ($query['customer_code'] ?? null) === 'CRM-0042'
                && ($query['per_page'] ?? null) === '2';
        });
    }

}
