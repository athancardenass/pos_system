<?php

namespace App\Http\Controllers;

use App\Services\Integration\Inventory\WebhookInbox;
use Illuminate\Http\Request;

class InventoryWebhookController extends Controller
{
    public function __invoke(Request $request, WebhookInbox $inbox)
    {
        $event = $request->validate([
            'event_id' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9._:-]+$/'],
            'sku' => ['required', 'string', 'max:100'],
        ]);
        $result = $inbox->accept($event);
        return response()->json($result, $result['duplicate'] ? 200 : 202);
    }
}
