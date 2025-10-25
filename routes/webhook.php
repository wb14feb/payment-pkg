<?php

use AnyTech\Jinah\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Jinah Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming webhooks from various payment services.
| All webhook routes are excluded from CSRF protection for compatibility
| with payment gateway notifications.
|
| The single agnostic handler automatically routes to the appropriate
| service based on the URL parameter or auto-detects from payload/headers.
|
*/

// Auto-detection webhook route (no service specified)
Route::post('/jinah/webhook', [WebhookController::class, 'handleAutoDetect'])
    ->name('jinah.webhook.auto')
    ->withoutMiddleware(['web', 'csrf']);

// Universal webhook routes - automatically detects and routes to service
Route::post('/jinah/webhook/{service}', [WebhookController::class, 'handle'])
    ->name('jinah.webhook')
    ->where('service', '[a-zA-Z0-9_-]+')
    ->withoutMiddleware(['web', 'csrf']);

// Alternative webhook routes with custom prefixes (configurable)
Route::group(['prefix' => config('jinah.webhook.route_prefix', 'payment-webhook')], function () {
    // Auto-detection route
    Route::post('/', [WebhookController::class, 'handleAutoDetect'])
        ->name('jinah.webhook.alt.auto')
        ->withoutMiddleware(['web', 'csrf']);
        
    // Service-specific routes
    Route::post('/{service}', [WebhookController::class, 'handle'])
        ->name('jinah.webhook.alt')
        ->where('service', '[a-zA-Z0-9_-]+')
        ->withoutMiddleware(['web', 'csrf']);
});

// Webhook health check endpoint
Route::get('/jinah/webhook/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'jinah-webhook',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'supported_services' => ['finpay', 'stripe', 'midtrans'],
    ]);
})->name('jinah.webhook.health');