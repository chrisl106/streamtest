<?php

use App\Http\Controllers\SiteFactoryController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\Auth\PatreonAuthController;
use App\Http\Controllers\FixtureController;
use App\Http\Controllers\LiveStreamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

// Central Dashboard Routes (No tenant scoping)
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::resource('sites', SiteFactoryController::class);
});

// Authentication Routes
Route::get('/login', [PatreonAuthController::class, 'showLoginForm'])->name('login');
Route::get('/auth/patreon', [PatreonAuthController::class, 'redirect'])->name('auth.patreon');
Route::get('/auth/patreon/callback', [PatreonAuthController::class, 'callback'])->name('auth.patreon.callback');
Route::post('/logout', [PatreonAuthController::class, 'logout'])->name('logout');
Route::get('/auth/check', [PatreonAuthController::class, 'checkMembership'])->name('auth.check');

// Per-Site Routes (with tenant scoping)
Route::middleware(['auth', 'require.auth', 'tenant.scope'])->group(function () {
    // Home/Dashboard
    Route::get('/', [VideoController::class, 'index'])->name('home');

    // Video Routes
    Route::get('/videos/{video}', [VideoController::class, 'show'])->name('videos.show');
    Route::post('/videos/{video}/progress', [VideoController::class, 'trackProgress'])->name('videos.progress');

    // Category Routes
    Route::get('/categories/{category}', [VideoController::class, 'category'])->name('categories.show');

    // Series Routes
    Route::get('/series/{series}', [VideoController::class, 'series'])->name('series.show');

    // Search
    Route::get('/search', [VideoController::class, 'search'])->name('search');

    // Fixtures
    Route::get('/fixtures', [FixtureController::class, 'index'])->name('fixtures.index');

    // Live Streams
    Route::get('/live', [LiveStreamController::class, 'index'])->name('live.index');
    Route::get('/live/{liveStream}', [LiveStreamController::class, 'show'])->name('live.show');

    // Onboarding
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

// Admin Routes (per-site)
Route::middleware(['auth', 'require.auth', 'tenant.scope', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Video Management
    Route::resource('videos', VideoResourceController::class);

    // Category Management
    Route::resource('categories', CategoryController::class);

    // Live Stream Management
    Route::resource('live-streams', LiveStreamController::class);

    // Analytics Dashboard
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/ban', [UserController::class, 'ban'])->name('users.ban');
    Route::patch('/users/{user}/unban', [UserController::class, 'unban'])->name('users.unban');
});

// API Routes for AJAX calls
Route::middleware(['auth', 'require.auth', 'tenant.scope'])->prefix('api')->name('api.')->group(function () {
    Route::post('/videos/{video}/track', [VideoController::class, 'trackProgress']);
    Route::get('/fixtures/upcoming', [FixtureController::class, 'apiUpcoming']);
    Route::get('/live/status', [LiveStreamController::class, 'apiStatus']);
});
