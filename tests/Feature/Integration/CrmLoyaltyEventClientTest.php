<?php

namespace Tests\Feature\Integration;

use App\Services\Integration\CRM\CustomerLoyaltyEventClient;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CrmLoyaltyEventClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.crm.base_url', 'https://crm.example.test');
        Config::set('services.crm.token', 'test-crm-token');
        Http::preventStrayRequests();
    }

    public function test_client_sends_a_stable_event_id_over_verified_https_with_explicit_crm_identity(): void
    {
        Http::fake([
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
        Http::assertSent(static fn (HttpRequest $request): bool =>
            ($request->data()['event_id'] ?? null) === 'pos:customer:refund:78'
            && ($request->data()['pos_refund_id'] ?? null) === 9
        );
    }
}
