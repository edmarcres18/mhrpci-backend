<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BiometricAuthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SiteInformationController;
use App\Http\Controllers\SiteSettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public API Routes with rate limiting (v1)
Route::prefix('v1')->group(function () {
    // Authentication Routes
    Route::post('/login', [AuthController::class, 'login'])
        ->name('api.login');
    Route::post('/biometric/login', [BiometricAuthController::class, 'biometricLogin'])
        ->name('api.biometric.login');

    // Site Information Contact API Endpoints (Public)
    Route::prefix('contacts')->group(function () {
        Route::get('/email', [SiteInformationController::class, 'fetchEmail'])
            ->middleware('throttle:120,1') // 120 requests per minute
            ->name('api.contacts.email');

        Route::get('/tel', [SiteInformationController::class, 'fetchTelNo'])
            ->middleware('throttle:120,1') // 120 requests per minute
            ->name('api.contacts.tel');

        Route::get('/phone', [SiteInformationController::class, 'fetchPhoneNo'])
            ->middleware('throttle:120,1') // 120 requests per minute
            ->name('api.contacts.phone');

        Route::get('/address', [SiteInformationController::class, 'fetchAddress'])
            ->middleware('throttle:120,1') // 120 requests per minute
            ->name('api.contacts.address');

        Route::get('/telegram', [SiteInformationController::class, 'fetchTelegram'])
            ->middleware('throttle:120,1') // 120 requests per minute
            ->name('api.contacts.telegram');

        Route::get('/facebook', [SiteInformationController::class, 'fetchFacebook'])
            ->middleware('throttle:120,1') // 120 requests per minute
            ->name('api.contacts.facebook');

        Route::get('/viber', [SiteInformationController::class, 'fetchViber'])
            ->middleware('throttle:120,1') // 120 requests per minute
            ->name('api.contacts.viber');

        Route::get('/whatsapp', [SiteInformationController::class, 'fetchWhatsapp'])
            ->middleware('throttle:120,1') // 120 requests per minute
            ->name('api.contacts.whatsapp');

        Route::get('/all', [SiteInformationController::class, 'fetchAllContacts'])
            ->middleware('throttle:120,1') // 120 requests per minute
            ->name('api.contacts.all');
    });

    // Public API for Inventory (v1)
    Route::prefix('inventories')->group(function () {
        Route::get('/{inventory}', [InventoryController::class, 'show'])
            ->middleware('throttle:120,1')
            ->name('api.inventories.show');
        Route::get('/locations', [InventoryController::class, 'locations'])
            ->middleware('throttle:120,1')
            ->name('api.inventories.locations');
    });

    // Public API for all Inventory items (v1) - No throttling as per user request
    Route::get('/inventories-all', [InventoryController::class, 'getAllInventories'])
        ->name('api.inventories.all');

    // Public API for total Inventory count (v1) - No throttling as per user request
    Route::get('/inventories-count', [InventoryController::class, 'getTotalInventoriesCount'])
        ->name('api.inventories.count');
});

// Authenticated API Routes (v1)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('api.logout');
    Route::post('/biometric/register', [BiometricAuthController::class, 'registerBiometric'])
        ->name('api.biometric.register');

    // Scan routes per user
    Route::prefix('scan')->group(function () {
        Route::post('/record', [ScanController::class, 'recordScan'])
            ->name('api.scan.record');
        Route::get('/daily-counts', [ScanController::class, 'getDailyScanCounts'])
            ->name('api.scan.daily-counts');
        Route::get('/recent', [ScanController::class, 'getRecentScans'])
            ->name('api.scan.recent');
    });
});


// Site Settings API (Public) - For logo and name display
Route::get('/site-settings', [SiteSettingsController::class, 'fetchSiteSettings'])
    ->middleware('throttle:200,1') // 200 requests per minute
    ->name('api.site-settings');
