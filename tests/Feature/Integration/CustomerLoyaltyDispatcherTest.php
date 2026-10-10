<?php

namespace Tests\Feature\Integration;

use App\Models\Customer;
use App\Models\CustomerOutboxEvent;
use App\Models\Employee;
use App\Models\SaleTransaction;
use App\Services\CustomerOutboxService;
use App\Services\Integration\CRM\CustomerLoyaltyDispatcher;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerLoyaltyDispatcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['services.crm.base_url' => 'https://crm.test', 'services.crm.token' => 'token']);
        Http::preventStrayRequests();
    }

    private function sale(?Customer $customer): SaleTransaction
    {
        $employee = Employee::first() ?? Employee::create([
            'role_id' => 1, 'first_name' => 'Test', 'last_name' => 'Cashier', 'username' => 'test',
            'password' => 'test', 'hire_date' => now(), 'status' => 'active',
        ]);
        return SaleTransaction::create([
            'customer_id' => $customer?->customer_id, 'employee_id' => $employee->employee_id,
            'transaction_date' => now(), 'subtotal' => 250, 'total_amount' => 250,
            'payment_method' => 'cash', 'status' => 'completed',
        ]);
    }

    public function test_registered_customer_event_is_delivered_once_and_walk_in_is_excluded(): void
    {
        $customer = Customer::create(['first_name' => 'Ana', 'last_name' => 'Santos', 'crm_external_id' => 'crm:customer:CUS-1']);
        $sale = $this->sale($customer);
        $outbox = app(CustomerOutboxService::class);
        $outbox->recordSaleEvent($sale->transaction_id, $customer->customer_id, 250);
        $walkIn = $this->sale(null);
        $outbox->recordSaleEvent($walkIn->transaction_id, null, 1000);
        $this->assertSame(1, CustomerOutboxEvent::count());
        Http::fake(['https://crm.test/api/v1/loyalty/events' => Http::response([
            'data' => ['event_id' => 'pos:customer:sale:1', 'points_delta' => 2, 'duplicate' => false],
        ], 201)]);
        $this->assertSame(['sent' => 1, 'failed' => 0], app(CustomerLoyaltyDispatcher::class)->dispatch());
        $this->assertSame(['sent' => 0, 'failed' => 0], app(CustomerLoyaltyDispatcher::class)->dispatch());
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['customer_external_id'] === 'crm:customer:CUS-1' && $request['amount'] === 250.0);
        $this->assertNotNull(CustomerOutboxEvent::first()->processed_at);
        // CRM owns the balance; POS must not credit a second local copy.
        $this->assertEquals(0, $customer->fresh()->loyalty_points);
    }

    public function test_failed_delivery_retries_same_identity_and_accepts_duplicate_acknowledgement(): void
    {
        $customer = Customer::create(['first_name' => 'Ana', 'last_name' => 'Santos', 'crm_external_id' => 'crm:customer:CUS-1']);
        $sale = $this->sale($customer);
        app(CustomerOutboxService::class)->recordSaleEvent($sale->transaction_id, $customer->customer_id, 50);
        Http::fake(['https://crm.test/api/v1/loyalty/events' => Http::sequence()
            ->push([], 503)->push(['data' => ['event_id' => 'pos:customer:sale:1', 'points_delta' => 1, 'duplicate' => true]])]);
        $dispatcher = app(CustomerLoyaltyDispatcher::class);
        $this->assertSame(['sent' => 0, 'failed' => 1], $dispatcher->dispatch());
        $this->assertNull(CustomerOutboxEvent::first()->processed_at);
        $this->assertSame(['sent' => 1, 'failed' => 0], $dispatcher->dispatch());
        $requests = Http::recorded();
        $this->assertSame($requests[0][0]->data(), $requests[1][0]->data());
        $this->assertSame(2, CustomerOutboxEvent::first()->attempts);
    }
}
