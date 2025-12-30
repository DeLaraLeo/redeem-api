<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RedeemController;
use App\Http\Controllers\WebhookController;


Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'redeem-api',
        'timestamp' => now(),
    ]);
});

Route::post('/redeem', [RedeemController::class, 'redeem']);

Route::post('/webhook/issuer-platform', [WebhookController::class, 'handle']);
