<?php

use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CompanyDetailsController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\FaqCategoryController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GalleryCategoryController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\PageSeoController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin/', 'middleware' => ['auth', 'is_admin']], function () {

    Route::get('/dashboard', [HomeController::class, 'adminHome'])->name('admin.dashboard');

    Route::get('/company-details', [CompanyDetailsController::class, 'index'])->name('admin.companyDetails');
    Route::post('/company-details', [CompanyDetailsController::class, 'update'])->name('admin.companyDetails');

    // Category CRUD
    Route::get('/category', [CategoryController::class, 'index'])->name('allcategory');
    Route::get('/parent-categories', [CategoryController::class, 'parentCategories'])->name('parent.categories');
    Route::post('/category', [CategoryController::class, 'store'])->name('category.store');
    Route::get('/category/{id}/edit', [CategoryController::class, 'edit'])->name('category.edit');
    Route::post('/category-update', [CategoryController::class, 'update'])->name('category.update');
    Route::delete('/category/{id}', [CategoryController::class, 'delete'])->name('category.delete');
    Route::delete('/category/{id}/image', [CategoryController::class, 'removeImage'])->name('category.removeImage');
    Route::delete('/category/{id}/meta-image', [CategoryController::class, 'removeMetaImage'])->name('category.removeMetaImage');
    Route::post('/category-status', [CategoryController::class, 'toggleStatus'])->name('category.toggleStatus');
    Route::get('/category-sort-list', [CategoryController::class, 'sortList'])->name('category.sortList');
    Route::post('/category-sort-update', [CategoryController::class, 'sortUpdate'])->name('category.sortUpdate');

    Route::get('/profile', [AdminProfileController::class, 'index'])->name('admin.profile');
    Route::post('/profile', [AdminProfileController::class, 'update'])->name('admin.profile.update');

    // Page SEO
    Route::get('/page-seo', [PageSeoController::class, 'index'])->name('page-seo.index');
    Route::get('/page-seo/{id}/edit', [PageSeoController::class, 'edit'])->name('page-seo.edit');
    Route::post('/page-seo/update', [PageSeoController::class, 'update'])->name('page-seo.update');

    // Sliders
    Route::get('/sliders', [SliderController::class, 'index'])->name('slider.index');
    Route::post('/sliders', [SliderController::class, 'store'])->name('slider.store');
    Route::get('/sliders/{id}/edit', [SliderController::class, 'edit'])->name('slider.edit');
    Route::post('/sliders/update', [SliderController::class, 'update'])->name('slider.update');
    Route::delete('/sliders/{id}', [SliderController::class, 'destroy'])->name('slider.delete');
    Route::delete('/sliders/{id}/image', [SliderController::class, 'removeImage'])->name('slider.removeImage');
    Route::post('/sliders/toggle-status', [SliderController::class, 'toggleStatus'])->name('slider.toggleStatus');

    // Testimonials
    Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonial.index');
    Route::post('/testimonials', [TestimonialController::class, 'store'])->name('testimonial.store');
    Route::get('/testimonials/{id}/edit', [TestimonialController::class, 'edit'])->name('testimonial.edit');
    Route::post('/testimonials/update', [TestimonialController::class, 'update'])->name('testimonial.update');
    Route::delete('/testimonials/{id}', [TestimonialController::class, 'destroy'])->name('testimonial.delete');
    Route::post('/testimonials/toggle-status', [TestimonialController::class, 'toggleStatus'])->name('testimonial.toggleStatus');

    // Contacts
    Route::get('/contacts', [ContactController::class, 'index'])->name('admin.contacts.index');
    Route::get('/contacts/{id}', [ContactController::class, 'show'])->name('admin.contacts.show');
    Route::post('/contacts/toggle-status', [ContactController::class, 'toggleStatus'])->name('admin.contacts.toggleStatus');
    Route::delete('/contacts/{id}', [ContactController::class, 'destroy'])->name('admin.contacts.delete');

    // Products (core showcase)
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::post('/products/update', [ProductController::class, 'update'])->name('products.update');
    Route::get('/products/{id}/manage', [ProductController::class, 'manage'])->name('products.manage');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.delete');
    Route::delete('/products/{id}/file', [ProductController::class, 'removeFile'])->name('products.removeFile');
    Route::post('/products/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggleStatus');
    Route::post('/products/toggle-featured', [ProductController::class, 'toggleFeatured'])->name('products.toggleFeatured');
    Route::get('/product-sort-list', [ProductController::class, 'sortList'])->name('products.sortList');
    Route::post('/product-sort-update', [ProductController::class, 'sortUpdate'])->name('products.sortUpdate');

    // Product children (per-product workspace tabs)
    Route::get('/products/{product}/images', [ProductImageController::class, 'list'])->name('product-images.list');
    Route::post('/products/{product}/images', [ProductImageController::class, 'store'])->name('product-images.store');
    Route::post('/product-images/{id}', [ProductImageController::class, 'update'])->name('product-images.update');
    Route::delete('/product-images/{id}', [ProductImageController::class, 'destroy'])->name('product-images.delete');

    // Product variants (per-product workspace tab)
    Route::get('/products/{product}/variants', [ProductVariantController::class, 'list'])->name('product-variants.list');
    Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])->name('product-variants.store');
    Route::post('/product-variants/{id}', [ProductVariantController::class, 'update'])->name('product-variants.update');
    Route::delete('/product-variants/{id}', [ProductVariantController::class, 'destroy'])->name('product-variants.delete');
    Route::post('/product-variants/{id}/default', [ProductVariantController::class, 'setDefault'])->name('product-variants.default');
    Route::post('/product-variants/toggle-stock', [ProductVariantController::class, 'toggleStock'])->name('product-variants.toggleStock');
    Route::delete('/product-variants/{id}/image', [ProductVariantController::class, 'removeImage'])->name('product-variants.removeImage');
    Route::post('/products/{product}/variant-groups', [ProductVariantController::class, 'syncGroups'])->name('product-variants.syncGroups');
    Route::post('/products/{id}/attributes', [ProductController::class, 'attributesSync'])->name('products.attributesSync');

    // FAQ
    Route::get('/faq-categories', [FaqCategoryController::class, 'index'])->name('faq-categories.index');
    Route::post('/faq-categories', [FaqCategoryController::class, 'store'])->name('faq-categories.store');
    Route::get('/faq-categories/{id}/edit', [FaqCategoryController::class, 'edit'])->name('faq-categories.edit');
    Route::post('/faq-categories/update', [FaqCategoryController::class, 'update'])->name('faq-categories.update');
    Route::delete('/faq-categories/{id}', [FaqCategoryController::class, 'destroy'])->name('faq-categories.delete');
    Route::post('/faq-categories/toggle-status', [FaqCategoryController::class, 'toggleStatus'])->name('faq-categories.toggleStatus');

    Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');
    Route::post('/faqs', [FaqController::class, 'store'])->name('faqs.store');
    Route::get('/faqs/{id}/edit', [FaqController::class, 'edit'])->name('faqs.edit');
    Route::post('/faqs/update', [FaqController::class, 'update'])->name('faqs.update');
    Route::delete('/faqs/{id}', [FaqController::class, 'destroy'])->name('faqs.delete');
    Route::post('/faqs/toggle-status', [FaqController::class, 'toggleStatus'])->name('faqs.toggleStatus');

    // Gallery
    Route::get('/gallery-categories', [GalleryCategoryController::class, 'index'])->name('gallery-categories.index');
    Route::post('/gallery-categories', [GalleryCategoryController::class, 'store'])->name('gallery-categories.store');
    Route::get('/gallery-categories/{id}/edit', [GalleryCategoryController::class, 'edit'])->name('gallery-categories.edit');
    Route::post('/gallery-categories/update', [GalleryCategoryController::class, 'update'])->name('gallery-categories.update');
    Route::delete('/gallery-categories/{id}', [GalleryCategoryController::class, 'destroy'])->name('gallery-categories.delete');
    Route::post('/gallery-categories/toggle-status', [GalleryCategoryController::class, 'toggleStatus'])->name('gallery-categories.toggleStatus');

    Route::get('/galleries', [GalleryController::class, 'index'])->name('galleries.index');
    Route::post('/galleries', [GalleryController::class, 'store'])->name('galleries.store');
    Route::get('/galleries/{id}/edit', [GalleryController::class, 'edit'])->name('galleries.edit');
    Route::post('/galleries/update', [GalleryController::class, 'update'])->name('galleries.update');
    Route::delete('/galleries/{id}', [GalleryController::class, 'destroy'])->name('galleries.delete');
    Route::post('/galleries/toggle-status', [GalleryController::class, 'toggleStatus'])->name('galleries.toggleStatus');
});
