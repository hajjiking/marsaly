<?php

use App\Http\Controllers\V1\AddressController;
use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CartController;
use App\Http\Controllers\V1\CatalogController;
use App\Http\Controllers\V1\DriverPanelController;
use App\Http\Controllers\V1\DriverRegistrationController;
use App\Http\Controllers\V1\HomeController;
use App\Http\Controllers\V1\MerchantPanelController;
use App\Http\Controllers\V1\MerchantRegistrationController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\OrderController;
use App\Http\Controllers\V1\ReviewController;
use App\Http\Controllers\V1\SupportTicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Marsaly REST API v1  (prefix: /api/v1, middleware: api + set.locale)
|--------------------------------------------------------------------------
| Auth: Laravel Sanctum bearer tokens. Roles: client, merchant, driver, admin.
| All responses use the ApiResponse envelope {success, data, message, errors}.
*/

// ---- Public -------------------------------------------------------------
Route::get('config', [HomeController::class, 'config']);

Route::post('auth/otp/request', [AuthController::class, 'requestOtp'])
    ->middleware('throttle:6,60');
Route::post('auth/otp/verify', [AuthController::class, 'verifyOtp'])
    ->middleware('throttle:15,60');

Route::get('categories', [CatalogController::class, 'categories']);
Route::get('merchants', [CatalogController::class, 'merchants']);
Route::get('merchants/{merchant}', [CatalogController::class, 'merchant']);
Route::get('products/{product}', [CatalogController::class, 'product']);
Route::get('banners', [CatalogController::class, 'banners']);
Route::get('announcements', [CatalogController::class, 'announcements']);

// ---- Authenticated (any role) --------------------------------------------
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::put('me', [AuthController::class, 'updateProfile']);
    Route::post('logout', [AuthController::class, 'logout']);

    // In-app notifications inbox + FCM device tokens
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'read']);
    Route::post('device-tokens', [NotificationController::class, 'storeDeviceToken']);
    Route::delete('device-tokens', [NotificationController::class, 'destroyDeviceToken']);

    // Support tickets
    Route::get('tickets', [SupportTicketController::class, 'index']);
    Route::post('tickets', [SupportTicketController::class, 'store']);
});

// ---- Client ---------------------------------------------------------------
Route::middleware(['auth:sanctum', 'role:client'])->group(function () {
    // Registration happens right after OTP verify for new clients/merch/driver.
    Route::post('merchants/register', [MerchantRegistrationController::class, 'store']);
    Route::post('drivers/register', [DriverRegistrationController::class, 'store']);

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'show']);
        Route::post('items', [CartController::class, 'addItem']);
        Route::put('items/{uid}', [CartController::class, 'updateItem']);
        Route::delete('items/{uid}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
        Route::post('promo', [CartController::class, 'applyPromo']);
        Route::post('quote', [CartController::class, 'quote']);
    });

    Route::apiResource('addresses', AddressController::class);

    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::patch('orders/{order}', [OrderController::class, 'update']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('orders/{order}/review', [ReviewController::class, 'store']);
});

// ---- Merchant registration bootstrap (any authenticated user without a merchant yet)
Route::middleware(['auth:sanctum', 'role:client,merchant'])->group(function () {
    Route::get('merchants/me', [MerchantRegistrationController::class, 'mine']);
    Route::get('drivers/me', [DriverRegistrationController::class, 'mine']);
});

// ---- Merchant panel -------------------------------------------------------
Route::middleware(['auth:sanctum', 'role:merchant'])->prefix('merchant')->group(function () {
    Route::put('profile', [MerchantPanelController::class, 'updateProfile']);
    Route::put('hours', [MerchantPanelController::class, 'updateHours']);
    Route::post('busy', [MerchantPanelController::class, 'toggleBusy']);

    Route::get('orders', [MerchantPanelController::class, 'orders']);
    Route::post('orders/{order}/status', [MerchantPanelController::class, 'orderStatus']);

    Route::get('menu', [MerchantPanelController::class, 'menu']);
    Route::post('sections', [MerchantPanelController::class, 'storeSection']);
    Route::put('sections/{section}', [MerchantPanelController::class, 'updateSection']);
    Route::delete('sections/{section}', [MerchantPanelController::class, 'destroySection']);
    Route::post('products', [MerchantPanelController::class, 'storeProduct']);
    Route::put('products/{product}', [MerchantPanelController::class, 'updateProduct']);
    Route::delete('products/{product}', [MerchantPanelController::class, 'destroyProduct']);

    Route::get('reviews', [MerchantPanelController::class, 'reviews']);
    Route::post('reviews/{review}/reply', [MerchantPanelController::class, 'replyReview']);
});

// ---- Driver panel ---------------------------------------------------------
Route::middleware(['auth:sanctum', 'role:driver'])->prefix('driver')->group(function () {
    Route::get('me', [DriverPanelController::class, 'me']);
    Route::post('availability', [DriverPanelController::class, 'setAvailability']);
    Route::post('location', [DriverPanelController::class, 'location'])->middleware('throttle:120,1');
    Route::get('offers', [DriverPanelController::class, 'offers']);
    Route::get('active', [DriverPanelController::class, 'active']);
    Route::get('history', [DriverPanelController::class, 'history']);
    Route::get('cash', [DriverPanelController::class, 'cash']);
    Route::get('earnings', [DriverPanelController::class, 'earnings']);
    Route::post('deliveries/{delivery}/accept', [DriverPanelController::class, 'accept']);
    Route::post('deliveries/{delivery}/decline', [DriverPanelController::class, 'decline']);
    Route::post('deliveries/{delivery}/action', [DriverPanelController::class, 'action']);
    Route::post('deliveries/{delivery}/failed', [DriverPanelController::class, 'failed']);
});
