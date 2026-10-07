<?php

use App\Http\Controllers\Api\AccountApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CatalogApiController;
use App\Http\Controllers\BagController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

// Mobile app API — plain /api (no version prefix). Token auth replaces the web session;
// the bag/checkout session is keyed by the X-Session-Id header (see ApiSession).
Route::middleware(['api_session', 'api_auth:optional'])->group(function () {
    // Auth
    Route::post('/auth/register', [AuthApiController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/auth/login', [AuthApiController::class, 'login'])->middleware('throttle:5,1');

    // Catalogue
    Route::get('/home', [CatalogApiController::class, 'home']);
    Route::get('/categories', [CatalogApiController::class, 'categories']);
    Route::get('/products', [CatalogApiController::class, 'products']);
    Route::get('/products/{slug}', [CatalogApiController::class, 'product']);
    Route::get('/search', [CatalogApiController::class, 'search'])->middleware('throttle:60,1');
    Route::get('/faqs', [CatalogApiController::class, 'faqs']);
    Route::get('/gallery', [CatalogApiController::class, 'gallery']);
    Route::get('/delivery', [CatalogApiController::class, 'delivery']);
    Route::get('/loyalty', [CatalogApiController::class, 'loyalty']);
    Route::post('/contact', [FrontendController::class, 'contactStore'])->middleware('throttle:10,1');
    Route::get('/track', [CatalogApiController::class, 'trackOrder'])->middleware('throttle:20,1');

    // Bag
    Route::get('/bag', [CatalogApiController::class, 'bag']);
    Route::post('/bag/add', [BagController::class, 'add']);
    Route::post('/bag/update', [BagController::class, 'update']);
    Route::post('/bag/remove', [BagController::class, 'remove']);

    // Checkout (guests allowed, same as web)
    Route::get('/checkout/init', [CatalogApiController::class, 'checkoutInit']);
    Route::post('/checkout/postcode', [CheckoutController::class, 'postcode']);
    Route::post('/checkout/coupon', [CheckoutController::class, 'coupon'])->middleware('throttle:20,1');
    Route::post('/checkout/place', [CheckoutController::class, 'place']);
    Route::post('/checkout/payment-confirm', [CheckoutController::class, 'paymentConfirm'])->middleware('throttle:30,1');
    Route::get('/checkout/success/{number}', [CheckoutController::class, 'successJson']);
    Route::post('/checkout/cancel', [CheckoutController::class, 'cancel']);

    // Shopper-only
    Route::middleware('api_auth')->group(function () {
        Route::post('/auth/logout', [AuthApiController::class, 'logout']);
        Route::get('/me', [AuthApiController::class, 'me']);
        Route::post('/me/profile', [AccountApiController::class, 'profile']);
        Route::post('/me/password', [AccountApiController::class, 'password']);

        Route::get('/orders', [AccountApiController::class, 'orders']);
        Route::get('/orders/{number}', [AccountApiController::class, 'order']);
        Route::post('/orders/{number}/reorder', [AccountApiController::class, 'reorder']);
        Route::post('/orders/{number}/pay', [AccountApiController::class, 'pay'])->middleware('throttle:30,1');
        Route::post('/orders/{number}/cancel', [AccountApiController::class, 'cancel']);

        Route::get('/addresses', [AccountApiController::class, 'addresses']);
        Route::post('/addresses', [AccountApiController::class, 'addressStore']);
        Route::post('/addresses/{id}', [AccountApiController::class, 'addressUpdate']);
        Route::post('/addresses/{id}/default', [AccountApiController::class, 'addressDefault']);
        Route::delete('/addresses/{id}', [AccountApiController::class, 'addressDestroy']);

        Route::get('/favourites', [AccountApiController::class, 'favourites']);
        Route::post('/favourites/toggle', [FavouriteController::class, 'toggle']);
        Route::post('/favourites/move-all', [AccountApiController::class, 'favouritesMoveAll']);

        Route::post('/reviews', [ReviewController::class, 'store'])->middleware('throttle:20,1');
        Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
    });
});
