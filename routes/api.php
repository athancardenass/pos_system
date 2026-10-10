<?php

use App\Http\Controllers\InventoryWebhookController;
use App\Http\Middleware\AuthenticateInventoryWebhook;
use Illuminate\Support\Facades\Route;

Route::post('/v1/inventory/product-updated', InventoryWebhookController::class)
    ->middleware([AuthenticateInventoryWebhook::class, 'throttle:120,1']);
