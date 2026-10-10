<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BagController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\WebhookController;
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

// Frontend Routes (Alam Mini Market grocery theme)
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
Route::get('/search-catalog', [FrontendController::class, 'searchCatalog'])->middleware('throttle:60,1')->name('search.catalog');
Route::get('/bag', [FrontendController::class, 'bag'])->name('bag');
Route::post('/bag/add', [BagController::class, 'add'])->name('bag.add');
Route::post('/bag/update', [BagController::class, 'update'])->name('bag.update');
Route::post('/bag/remove', [BagController::class, 'remove'])->name('bag.remove');
Route::get('/bag/data', [BagController::class, 'show'])->name('bag.data');
Route::post('/checkout/place', [CheckoutController::class, 'place'])->middleware('throttle:10,1')->name('checkout.place');
Route::post('/checkout/coupon', [CheckoutController::class, 'coupon'])->middleware('throttle:20,1')->name('checkout.coupon');
Route::post('/checkout/postcode', [CheckoutController::class, 'postcode'])->name('checkout.postcode');
Route::post('/checkout/payment-confirm', [CheckoutController::class, 'paymentConfirm'])->middleware('throttle:30,1')->name('checkout.payment-confirm');
Route::post('/webhooks/stripe', [WebhookController::class, 'stripe'])->name('webhooks.stripe');
Route::post('/webhooks/paypal', [WebhookController::class, 'paypal'])->name('webhooks.paypal');
Route::post('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
Route::get('/order/success/{number}', [CheckoutController::class, 'success'])->name('order.success');
Route::get('/checkout', [FrontendController::class, 'checkout'])->name('checkout');
Route::get('/account', [AccountController::class, 'index'])->name('account');
Route::get('/account/loyalty', [AccountController::class, 'loyalty'])->name('account.loyalty');
Route::get('/account/addresses', [AccountController::class, 'addresses'])->name('account.addresses');
Route::get('/account/details', [AccountController::class, 'details'])->name('account.details');
Route::get('/account/password', [AccountController::class, 'passwordForm'])->name('account.password.form');
Route::get('/account/lists', [AccountController::class, 'lists'])->name('lists.index');
Route::get('/account/orders/{number}', [AccountController::class, 'show'])->name('account.order');
Route::post('/account/orders/{number}/reorder', [AccountController::class, 'reorder'])->name('account.reorder');
Route::post('/account/orders/{number}/repeat-weekly', [AccountController::class, 'repeatWeekly'])->name('account.repeat');
Route::post('/account/repeats/{id}/cancel', [AccountController::class, 'cancelRepeat'])->name('account.repeat.cancel');
Route::post('/account/lists', [ShoppingListController::class, 'store'])->name('lists.store');
Route::delete('/account/lists/{id}', [ShoppingListController::class, 'destroy'])->name('lists.delete');
Route::post('/account/lists/{id}/items', [ShoppingListController::class, 'addItem'])->name('lists.items.store');
Route::delete('/account/lists/{listId}/items/{itemId}', [ShoppingListController::class, 'removeItem'])->name('lists.items.delete');
Route::post('/account/lists/{id}/add-all', [ShoppingListController::class, 'addAllToBag'])->name('lists.addAll');
Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::get('/recipes/{slug}', [RecipeController::class, 'show'])->name('recipes.show');
Route::post('/recipes/{id}/add-all', [RecipeController::class, 'addAllToBag'])->name('recipes.addAll');
Route::post('/account/orders/{number}/pay', [AccountController::class, 'pay'])->middleware('throttle:30,1')->name('account.pay');
Route::post('/account/orders/{number}/cancel', [AccountController::class, 'cancel'])->name('account.cancel');
Route::post('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
Route::post('/account/password', [AccountController::class, 'password'])->name('account.password');
Route::post('/account/addresses', [AddressController::class, 'store'])->name('account.addresses.store');
Route::post('/account/addresses/{id}', [AddressController::class, 'update'])->name('account.addresses.update');
Route::post('/account/addresses/{id}/default', [AddressController::class, 'setDefault'])->name('account.addresses.default');
Route::delete('/account/addresses/{id}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');
Route::get('/favourites', [FavouriteController::class, 'index'])->name('favourites');
Route::post('/favourites/toggle', [FavouriteController::class, 'toggle'])->name('favourites.toggle');
Route::post('/favourites/move-all', [FavouriteController::class, 'moveAll'])->name('favourites.move-all');
Route::post('/reviews', [ReviewController::class, 'store'])->middleware('throttle:20,1')->name('reviews.store');
Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
Route::get('/faq', [FrontendController::class, 'faq'])->name('faq');
Route::get('/track', [FrontendController::class, 'track'])->name('track');
Route::post('/track', [FrontendController::class, 'trackLookup'])->middleware('throttle:20,1')->name('track.lookup');
Route::get('/loyalty', [FrontendController::class, 'loyalty'])->name('loyalty');
Route::get('/delivery', [FrontendController::class, 'delivery'])->name('delivery');
Route::get('/contact', [FrontendController::class, 'contact'])->name('contact');
Route::post('/contact', [FrontendController::class, 'contactStore'])->middleware('throttle:10,1')->name('contact.store');
Route::post('/newsletter', [FrontendController::class, 'subscribeStore'])->middleware('throttle:10,1')->name('newsletter.subscribe');
Route::post('/notify', [FrontendController::class, 'notifyStore'])->middleware('throttle:10,1')->name('notify.store');

// Static Pages
Route::get('/privacy-policy', [FrontendController::class, 'privacy'])->name('privacy');
Route::get('/terms-of-service', [FrontendController::class, 'terms'])->name('terms');
Route::get('/refund-policy', [FrontendController::class, 'refund'])->name('refund');
