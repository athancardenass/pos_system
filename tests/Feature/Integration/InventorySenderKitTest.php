<?php

namespace Tests\Feature\Integration;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventorySenderKitTest extends TestCase
{
    use RefreshDatabase;

    public function test_kit_sender_retains_failures_and_retries_same_event_without_redelivery(): void
    {
        require_once base_path('handoff/inventory-webhook-sender/files/app/Services/PosWebhookSender.php');
        Schema::create('pos_webhook_outbox', function (Blueprint $table) {
            $table->string('event_id')->primary(); $table->string('sku');
            $table->integer('attempts')->default(0); $table->dateTime('available_at');
            $table->dateTime('delivered_at')->nullable(); $table->string('last_error')->nullable(); $table->timestamp('created_at');
        });
        config(['pos_webhook.url' => 'https://pos.test/api/v1/inventory/product-updated', 'pos_webhook.token' => 'receiver-token']);
        Http::preventStrayRequests();
        Http::fake(['https://pos.test/api/v1/inventory/product-updated' => Http::sequence()
            ->push([], 503)->push(['accepted' => true, 'duplicate' => true], 200)]);
        DB::table('pos_webhook_outbox')->insert(['event_id' => 'stable-id', 'sku' => 'BEV-3411', 'available_at' => now(), 'created_at' => now()]);
        $sender = new \App\Services\PosWebhookSender();
        $this->assertSame(['sent' => 0, 'failed' => 1], $sender->sendPending());
        $this->assertNull(DB::table('pos_webhook_outbox')->value('delivered_at'));
        $this->assertSame(['sent' => 0, 'failed' => 0], $sender->sendPending());
        $this->travel(6)->seconds();
        $this->assertSame(['sent' => 1, 'failed' => 0], $sender->sendPending());
        $this->assertNotNull(DB::table('pos_webhook_outbox')->value('delivered_at'));
        $this->assertSame(['sent' => 0, 'failed' => 0], $sender->sendPending());
        $requests = Http::recorded();
        $this->assertSame($requests[0][0]->data(), $requests[1][0]->data());
        Http::assertSent(fn ($request) => $request['event_id'] === 'inventory:stable-id'
            && $request['sku'] === 'BEV-3411' && $request->hasHeader('Authorization', 'Bearer receiver-token'));
        Http::assertSentCount(2);
    }
}
