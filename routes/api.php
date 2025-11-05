<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Public API routes (no auth required)
Route::get('/fixtures/upcoming', [App\Http\Controllers\FixtureController::class, 'apiUpcoming']);
Route::get('/live/status', [App\Http\Controllers\LiveStreamController::class, 'apiStatus']);

// Protected API routes
Route::middleware(['auth:sanctum', 'require.auth', 'tenant.scope'])->group(function () {
    // Video tracking
    Route::post('/videos/{video}/track', [App\Http\Controllers\VideoController::class, 'trackProgress']);

    // User preferences
    Route::get('/user/preferences', [App\Http\Controllers\UserController::class, 'getPreferences']);
    Route::post('/user/preferences', [App\Http\Controllers\UserController::class, 'updatePreferences']);

    // Mobile app endpoints
    Route::get('/videos', [App\Http\Controllers\Api\VideoController::class, 'index']);
    Route::get('/videos/{video}', [App\Http\Controllers\Api\VideoController::class, 'show']);
    Route::get('/categories', [App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/fixtures', [App\Http\Controllers\Api\FixtureController::class, 'index']);
    Route::get('/live-streams', [App\Http\Controllers\Api\LiveStreamController::class, 'index']);

    // Webhook endpoints
    Route::post('/webhooks/patreon', [App\Http\Controllers\WebhookController::class, 'patreon']);
    Route::post('/webhooks/stripe', [App\Http\Controllers\WebhookController::class, 'stripe']);
});
