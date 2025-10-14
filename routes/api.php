<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteInformationController;
use App\Http\Controllers\SiteSettingsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public API Routes with rate limiting (v1)
Route::prefix('v1')->group(function () {
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
});

// Site Settings API (Public) - For logo and name display
Route::get('/site-settings', [SiteSettingsController::class, 'fetchSiteSettings'])
    ->middleware('throttle:200,1') // 200 requests per minute
    ->name('api.site-settings');
