<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateInventoryWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $token = (string) config('services.inventory_webhook_token');
        if ($token === '') return response()->json(['error' => 'Webhook authentication is not configured.'], 503);
        $provided = $request->bearerToken();
        if (! is_string($provided) || ! hash_equals($token, $provided)) {
            return response()->json(['error' => 'A valid bearer token is required.'], 401);
        }
        return $next($request);
    }
}
