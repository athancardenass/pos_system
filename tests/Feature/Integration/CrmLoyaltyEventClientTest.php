<?php

namespace Tests\Feature\Integration;

use App\Services\Integration\CRM\CustomerLoyaltyEventClient;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmLoyaltyEventClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.crm.base_url', 'https://crm.example.test');
        Config::set('services.crm.token', 'test-crm-token');
        Http::preventStrayRequests();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $employee = \App\Models\Employee::create(['role_id' => 1, 'first_name' => 'Test', 'last_name' => 'Cashier', 'username' => 'crm-test', 'password' => 'test', 'hire_date' => now(), 'status' => 'active']);
        $customer = \App\Models\Customer::create(['first_name' => 'Test', 'last_name' => 'Customer', 'crm_external_id' => 'crm:customer:CRM-0042']);
        DB::table('sale_transaction')->insert(['transaction_id' => 41, 'employee_id' => $employee->employee_id, 'customer_id' => $customer->customer_id, 'transaction_date' => now(), 'subtotal' => 275, 'total_amount' => 250, 'payment_method' => 'cash', 'status' => 'completed', 'discount_snapshot' => json_encode(['amount' => 25]), 'tax_snapshot' => json_encode(['vat_amount' => 26.79])]);
        $product = \App\Models\Product::create(['product_name' => 'Test item', 'barcode' => '4006381333931', 'unit_price' => 275, 'cost_price' => 100, 'reorder_level' => 1]);
        \App\Models\SaleDetail::create(['transaction_id' => 41, 'product_id' => $product->product_id, 'quantity' => 1, 'unit_price' => 275, 'subtotal' => 275]);
    }

    public function test_client_sends_a_stable_event_id_over_verified_https_with_explicit_crm_identity(): void
    {
        Http::fake([
            'https://crm.example.test/api/v1/orders' => Http::response(['data' => ['event_id' => 'pos:customer:order:41', 'order_id' => 10]], 201),
            'https://crm.example.test/api/v1/loyalty/events' => Http::response([
                'data' => ['event_id' => 'pos:customer:sale:77', 'points_delta' => 2, 'duplicate' => false],
            ], 201),
        ]);

        $result = app(CustomerLoyaltyEventClient::class)->send(
            77,
            'sale.completed',
            41,
            null,
            'crm:customer:CRM-0042',
            250.00,
        );

        $this->assertSame(['status' => 201, 'accepted' => true, 'duplicate' => false], $result);
        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://crm.example.test/api/v1/orders'
            && $request['event_id'] === 'pos:customer:order:41' && $request['pos_transaction_id'] === 41
            && $request['discount'] === 25.0 && $request['tax'] === 26.79
            && $request['items'][0]['name'] === 'Test item' && $request['items'][0]['total_price'] === 275.0);
        Http::assertSent(static function (HttpRequest $request): bool {
            $data = $request->data();

            return parse_url($request->url(), PHP_URL_SCHEME) === 'https'
                && parse_url($request->url(), PHP_URL_PATH) === '/api/v1/loyalty/events'
                && $request->hasHeader('Authorization', 'Bearer test-crm-token')
                && ($data['event_id'] ?? null) === 'pos:customer:sale:77'
                && ($data['pos_transaction_id'] ?? null) === 41
                && ($data['customer_external_id'] ?? null) === 'crm:customer:CRM-0042'
                && ($data['amount'] ?? null) === 250.0;
        });
    }

    public function test_client_refuses_plain_http_and_does_not_send_the_token(): void
    {
        Config::set('services.crm.base_url', 'http://crm.example.test');

        $result = app(CustomerLoyaltyEventClient::class)->send(
            77,
            'sale.completed',
            41,
            null,
            'crm:customer:CRM-0042',
            250.00,
        );

        $this->assertSame(['status' => 503, 'accepted' => false, 'duplicate' => false], $result);
        Http::assertNothingSent();
    }

    public function test_refund_event_uses_refund_id_and_propagates_receiver_duplicate_acknowledgement(): void
    {
        DB::table('sale_transaction')->where('transaction_id', 41)->update(['status' => 'refunded']);
        Http::fake([
            'https://crm.example.test/api/v1/loyalty/events' => Http::response([
                'data' => ['event_id' => 'pos:customer:refund:78', 'points_delta' => -2, 'duplicate' => true],
            ], 200),
        ]);

        $result = app(CustomerLoyaltyEventClient::class)->send(
            78,
            'sale.refunded',
            41,
            9,
            'crm:customer:CRM-0042',
            250.00,
        );

        $this->assertSame(['status' => 200, 'accepted' => true, 'duplicate' => true], $result);
        Http::assertSentCount(1);
        Http::assertSent(static fn (HttpRequest $request): bool =>
            ($request->data()['event_id'] ?? null) === 'pos:customer:refund:78'
            && ($request->data()['pos_refund_id'] ?? null) === 9
        );
    }

    public function test_failed_order_retries_with_stable_order_and_loyalty_ids(): void
    {
        Http::fake([
            'https://crm.example.test/api/v1/loyalty/events' => Http::sequence()
                ->push(['data' => ['event_id' => 'pos:customer:sale:77', 'points_delta' => 2, 'duplicate' => false]], 201)
                ->push(['data' => ['event_id' => 'pos:customer:sale:77', 'points_delta' => 0, 'duplicate' => true]], 200),
            'https://crm.example.test/api/v1/orders' => Http::sequence()->push([], 503)
                ->push(['data' => ['event_id' => 'pos:customer:order:41', 'order_id' => 10]], 200),
        ]);
        $client = app(CustomerLoyaltyEventClient::class);
        $this->assertFalse($client->send(77, 'sale.completed', 41, null, 'crm:customer:CRM-0042', 250)['accepted']);
        $result = $client->send(77, 'sale.completed', 41, null, 'crm:customer:CRM-0042', 250);
        $this->assertTrue($result['accepted']);
        $this->assertTrue($result['duplicate']);
        $orders = Http::recorded(fn ($request) => str_ends_with($request->url(), '/orders'))->values();
        $this->assertCount(2, $orders);
        $this->assertSame($orders[0][0]->data(), $orders[1][0]->data());
    }

    public function test_unmatched_order_acknowledgement_keeps_delivery_pending(): void
    {
        Http::fake([
            'https://crm.example.test/api/v1/loyalty/events' => Http::response(['data' => ['event_id' => 'pos:customer:sale:77', 'points_delta' => 2, 'duplicate' => false]], 201),
            'https://crm.example.test/api/v1/orders' => Http::response(['data' => ['event_id' => 'another-order', 'order_id' => 10]], 201),
        ]);
        $this->assertFalse(app(CustomerLoyaltyEventClient::class)->send(77, 'sale.completed', 41, null, 'crm:customer:CRM-0042', 250)['accepted']);
    }

    public function test_noncompleted_sale_does_not_create_an_order(): void
    {
        DB::table('sale_transaction')->where('transaction_id', 41)->update(['status' => 'refunded']);
        Http::fake(['https://crm.example.test/api/v1/loyalty/events' => Http::response(['data' => ['event_id' => 'pos:customer:sale:77', 'points_delta' => 2, 'duplicate' => false]], 201)]);
        $this->assertFalse(app(CustomerLoyaltyEventClient::class)->send(77, 'sale.completed', 41, null, 'crm:customer:CRM-0042', 250)['accepted']);
        Http::assertSentCount(1);
    }
}
