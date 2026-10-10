<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

uses(RefreshDatabase::class);

function bulkPhotoAdmin(): User
{
    return User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
        'user_type' => 1,
        'status' => 1,
    ]);
}

function bulkPhotoFixtures(): array
{
    $cat = Category::create(['name' => 'Fresh Meat', 'slug' => 'fresh-meat']);
    $product = Product::create(['category_id' => $cat->id, 'name' => 'Lamb Leg', 'slug' => 'lamb-leg']);
    $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'LAMB-500', 'mrp' => 12.99, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);

    return compact('cat', 'product', 'variant');
}

test('bulk photos page requires admin', function () {
    $this->get(route('products.bulkPhotos'))->assertRedirect(route('login'));
    $this->post(route('products.bulkPhotosUpload'), [])->assertRedirect(route('login'));
    $this->post(route('products.bulkPhotosConfirm'), [])->assertRedirect(route('login'));
});

test('bulk photos upload matches sku filenames and confirm saves webp', function () {
    Storage::fake('local');
    $admin = bulkPhotoAdmin();
    ['product' => $product, 'variant' => $variant] = bulkPhotoFixtures();

    $response = $this->actingAs($admin)->post(route('products.bulkPhotosUpload'), [
        'photos' => [
            UploadedFile::fake()->image('LAMB-500.jpg', 400, 400),
            UploadedFile::fake()->image('mystery-photo.jpg', 400, 400),
        ],
    ]);
    $response->assertOk();
    $response->assertViewHas('matches', function ($matches) {
        $byFile = collect($matches)->keyBy('file');

        return $byFile['LAMB-500.jpg']['variant_id'] !== null
            && $byFile['LAMB-500.jpg']['also_hero'] === true
            && $byFile['mystery-photo.jpg']['product_id'] === null;
    });
    $token = $response->getOriginalContent()->getData()['token'];
    expect(Storage::exists("bulk-photos/{$token}/LAMB-500.jpg"))->toBeTrue();

    $confirm = $this->actingAs($admin)->post(route('products.bulkPhotosConfirm'), [
        'token' => $token,
        'items' => [
            ['file' => 'LAMB-500.jpg', 'product_id' => $product->id, 'variant_id' => $variant->id, 'target' => 'variant', 'also_hero' => true],
        ],
    ]);
    $confirm->assertRedirect(route('products.index'));
    $confirm->assertSessionHas('success');

    $variant->refresh();
    $product->refresh();
    expect($variant->image)->toStartWith('/uploads/products/variants/')
        ->and($variant->image)->toEndWith('.webp')
        ->and(file_exists(public_path($variant->image)))->toBeTrue()
        ->and($product->hero_image)->toStartWith('/uploads/products/')
        ->and(Storage::exists("bulk-photos/{$token}"))->toBeFalse();

    @unlink(public_path($variant->image));
    @unlink(public_path($product->hero_image));
});

test('bulk photos search finds variants by sku or product name', function () {
    $admin = bulkPhotoAdmin();
    bulkPhotoFixtures();

    $this->actingAs($admin)->get(route('products.bulkPhotosSearch', ['q' => 'lamb-5']))
        ->assertOk()
        ->assertJsonFragment(['sku' => 'LAMB-500']);
    $this->actingAs($admin)->get(route('products.bulkPhotosSearch', ['q' => 'x']))
        ->assertOk()
        ->assertExactJson([]);
});

test('attach-photos command previews then commits sku-named files', function () {
    ['product' => $product, 'variant' => $variant] = bulkPhotoFixtures();

    $dir = storage_path('app/test-attach-photos');
    @mkdir($dir, 0755, true);
    Image::canvas(300, 300, '#a3c585')->save("{$dir}/LAMB-500.jpg");
    Image::canvas(300, 300, '#c58585')->save("{$dir}/random-name.png");

    $this->artisan('products:attach-photos', ['dir' => $dir])
        ->assertSuccessful()
        ->expectsOutputToContain('LAMB-500.jpg  →  LAMB-500 — Lamb Leg')
        ->expectsOutputToContain('random-name.png  →  NO MATCH');
    expect($variant->fresh()->image)->toBeNull();

    $this->artisan('products:attach-photos', ['dir' => $dir, '--commit' => true, '--hero' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Attached 1 photo(s).');

    $variant->refresh();
    $product->refresh();
    expect($variant->image)->toStartWith('/uploads/products/variants/')
        ->and(file_exists(public_path($variant->image)))->toBeTrue()
        ->and($product->hero_image)->toStartWith('/uploads/products/');

    @unlink(public_path($variant->image));
    @unlink(public_path($product->hero_image));
    array_map('unlink', glob("{$dir}/*") ?: []);
    @rmdir($dir);
});

test('bulk photos rejects bad input safely', function () {
    $admin = bulkPhotoAdmin();
    ['product' => $product, 'variant' => $variant] = bulkPhotoFixtures();

    // Staged file route: bad token and missing file are 404, never a leak.
    $this->actingAs($admin)->get(route('products.bulkPhotosFile', ['token' => 'nope', 'name' => 'x.jpg']))->assertNotFound();
    $this->actingAs($admin)->get(route('products.bulkPhotosFile', ['token' => str_repeat('a', 32), 'name' => 'x.jpg']))->assertNotFound();

    // Expired token on confirm redirects back with an error, saves nothing.
    $this->actingAs($admin)->post(route('products.bulkPhotosConfirm'), [
        'token' => str_repeat('b', 32),
        'items' => [['file' => 'x.jpg', 'product_id' => $product->id, 'target' => 'hero']],
    ])->assertRedirect(route('products.bulkPhotos'))->assertSessionHas('error');
    expect($product->fresh()->hero_image)->toBeNull();

    // Non-image upload is rejected by validation.
    $this->actingAs($admin)->post(route('products.bulkPhotosUpload'), [
        'photos' => [UploadedFile::fake()->create('notes.txt', 10, 'text/plain')],
    ])->assertSessionHasErrors('photos.0');
});

test('bulk photos confirm skips variant assigned to the wrong product', function () {
    Storage::fake('local');
    $admin = bulkPhotoAdmin();
    ['product' => $product, 'variant' => $variant] = bulkPhotoFixtures();
    $other = Product::create(['category_id' => $product->category_id, 'name' => 'Beef', 'slug' => 'beef']);

    $upload = $this->actingAs($admin)->post(route('products.bulkPhotosUpload'), [
        'photos' => [UploadedFile::fake()->image('LAMB-500.jpg', 400, 400)],
    ]);
    $upload->assertOk();
    $token = $upload->getOriginalContent()->getData()['token'];

    $confirm = $this->actingAs($admin)->post(route('products.bulkPhotosConfirm'), [
        'token' => $token,
        'items' => [
            ['file' => 'LAMB-500.jpg', 'product_id' => $other->id, 'variant_id' => $variant->id, 'target' => 'variant'],
        ],
    ]);
    $confirm->assertRedirect(route('products.index'));
    $confirm->assertSessionHas('success');
    expect($variant->fresh()->image)->toBeNull()
        ->and($other->fresh()->hero_image)->toBeNull();
});
