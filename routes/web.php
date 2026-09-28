<?php

use App\Http\Controllers\FrontendController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/clear', function () {
    Auth::logout();
    session()->flush();
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');

    return 'Cleared!';
});

Route::fallback(function () {
    return redirect('/');
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

// Dashboard (must keep)
Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

// Frontend Routes (OriginSpaces showcase theme)
Route::get('/', [FrontendController::class, 'index'])->name('home');
Route::get('/about', [FrontendController::class, 'about'])->name('about');
Route::get('/collections', [FrontendController::class, 'collections'])->name('collections');
Route::get('/product/{slug}', [FrontendController::class, 'productShow'])->name('product.show');
Route::get('/custom-build', [FrontendController::class, 'customBuild'])->name('custom-build');
Route::post('/enquiries', [FrontendController::class, 'enquiriesStore'])->name('enquiries.store');
Route::get('/gallery', [FrontendController::class, 'gallery'])->name('gallery');
Route::get('/downloads', [FrontendController::class, 'downloads'])->name('downloads');
Route::get('/downloads/{id}/file', [FrontendController::class, 'downloadFile'])->name('downloads.file');
Route::get('/contact', [FrontendController::class, 'contact'])->name('contact');
Route::post('/contact', [FrontendController::class, 'contactStore'])->name('contact.store');

// Static Pages
Route::get('/privacy-policy', [FrontendController::class, 'privacy'])->name('privacy');
Route::get('/terms-of-service', [FrontendController::class, 'terms'])->name('terms');
