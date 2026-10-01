<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BagController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::fallback(function () {
    abort(404);
});

require __DIR__.'/admin.php';

Auth::routes([
    'login' => true,
    'logout' => true,
    'register' => false,
    'reset' => false,
    'confirm' => false,
    'verify' => false,
]);

// Shopper registration (same users table, user_type 0 — same auth as admins)
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.store');

// Dashboard (must keep)
Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

// Frontend Routes (Evergreen grocery theme)
Route::get('/', [FrontendController::class, 'index'])->name('home');
Route::get('/about', [FrontendController::class, 'about'])->name('about');
Route::get('/shop', [FrontendController::class, 'shop'])->name('shop');
Route::get('/shop/offers', [FrontendController::class, 'shopOffers'])->name('shop.offers');
Route::get('/shop/{category}', [FrontendController::class, 'shop'])->where('category', '[A-Za-z0-9\-]+')->name('shop.category');
Route::get('/offers', function () {
    return redirect()->route('shop', ['only_offers' => 1], 301);
});
Route::get('/product/{slug}', [FrontendController::class, 'productShow'])->name('product.show');
Route::get('/gallery', [FrontendController::class, 'gallery'])->name('gallery');
Route::get('/bag', [FrontendController::class, 'bag'])->name('bag');
Route::post('/bag/add', [BagController::class, 'add'])->name('bag.add');
Route::post('/bag/update', [BagController::class, 'update'])->name('bag.update');
Route::post('/bag/remove', [BagController::class, 'remove'])->name('bag.remove');
Route::get('/bag/data', [BagController::class, 'show'])->name('bag.data');
Route::post('/checkout/place', [CheckoutController::class, 'place'])->name('checkout.place');
Route::post('/checkout/payment-confirm', [CheckoutController::class, 'paymentConfirm'])->name('checkout.payment-confirm');
Route::post('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
Route::get('/order/success/{number}', [CheckoutController::class, 'success'])->name('order.success');
Route::get('/checkout', [FrontendController::class, 'checkout'])->name('checkout');
Route::get('/account', [AccountController::class, 'index'])->name('account');
Route::get('/account/orders/{number}', [AccountController::class, 'show'])->name('account.order');
Route::post('/account/orders/{number}/reorder', [AccountController::class, 'reorder'])->name('account.reorder');
Route::post('/account/orders/{number}/pay', [AccountController::class, 'pay'])->name('account.pay');
Route::post('/account/orders/{number}/cancel', [AccountController::class, 'cancel'])->name('account.cancel');
Route::post('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
Route::post('/account/password', [AccountController::class, 'password'])->name('account.password');
Route::get('/faq', [FrontendController::class, 'faq'])->name('faq');
Route::get('/track', [FrontendController::class, 'track'])->name('track');
Route::post('/track', [FrontendController::class, 'trackLookup'])->middleware('throttle:20,1')->name('track.lookup');
Route::get('/contact', [FrontendController::class, 'contact'])->name('contact');
Route::post('/contact', [FrontendController::class, 'contactStore'])->name('contact.store');

// Static Pages
Route::get('/privacy-policy', [FrontendController::class, 'privacy'])->name('privacy');
Route::get('/terms-of-service', [FrontendController::class, 'terms'])->name('terms');
Route::get('/refund-policy', [FrontendController::class, 'refund'])->name('refund');
