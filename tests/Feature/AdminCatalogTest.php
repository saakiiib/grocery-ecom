<?php

use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\Contact;
use App\Models\Download;
use App\Models\Enquiry;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\FloorZone;
use App\Models\Gallery;
use App\Models\GalleryCategory;
use App\Models\PageSeo;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function adminUser(): User
{
    return User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
        'user_type' => 1,
    ]);
}

test('category persists SEO and video fields', function () {
    $cat = Category::create([
        'name' => 'Expandable Homes', 'slug' => 'expandable-homes',
        'meta_title' => 'Expandable Homes UK', 'meta_description' => 'Desc',
        'meta_keywords' => 'a, b', 'meta_image' => '/uploads/category/x.webp',
        'video_url' => 'https://example.com/v.mp4',
    ]);

    expect($cat->meta_title)->toBe('Expandable Homes UK')
        ->and($cat->video_url)->toBe('https://example.com/v.mp4');
});

test('product creation keeps price optional and seeds tech specs', function () {
    $admin = adminUser();
    $cat = Category::create(['name' => 'Kitchen', 'slug' => 'kitchen']);

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'name' => 'The Aster 38', 'model_code' => 'HS-EXP-38/AST', 'category_id' => $cat->id,
    ]);

    $response->assertOk()->assertJson(['message' => 'Product created successfully']);

    $product = Product::where('model_code', 'HS-EXP-38/AST')->firstOrFail();
    expect($product->base_price)->toBeNull()
        ->and($product->techSpecs)->toHaveCount(4)
        ->and($product->slug)->toContain('aster');

    // SEO array shape for frontend SEOMeta/OpenGraph
    $seo = $product->seoArray();
    expect($seo)->toHaveKeys(['title', 'description', 'keywords', 'image']);
});

test('product rejects duplicate model code and invalid option group', function () {
    $admin = adminUser();
    Product::create(['name' => 'A', 'slug' => 'a', 'model_code' => 'DUP-1']);

    $this->actingAs($admin)->post(route('products.store'), [
        'name' => 'B', 'model_code' => 'DUP-1',
    ])->assertStatus(302)->assertInvalid('model_code');

    $product = Product::where('model_code', 'DUP-1')->first();
    $this->actingAs($admin)->post(route('product-options.store', $product->id), [
        'group' => 'invalid', 'name' => 'X',
    ])->assertStatus(302)->assertInvalid('group');
});

test('product option price empty means included, finish keeps swatch', function () {
    $product = Product::create(['name' => 'P', 'slug' => 'p', 'model_code' => 'M-1']);
    $opt = ProductOption::create([
        'product_id' => $product->id, 'group' => 'finish',
        'name' => 'Smoked Oak', 'price_delta' => null, 'swatch_color' => '#5A4636',
    ]);

    expect($opt->price_delta)->toBeNull()->and($opt->swatch_color)->toBe('#5A4636');

    // video fallback: product empty -> category video
    $cat = Category::create(['name' => 'C', 'slug' => 'c', 'video_url' => 'https://example.com/cat.mp4']);
    $product->update(['category_id' => $cat->id, 'video_url' => null]);
    expect($product->fresh()->effectiveVideoUrl())->toBe('https://example.com/cat.mp4');
});

test('supporting modules persist with relations', function () {
    $faqCat = FaqCategory::create(['name' => 'Lead Times', 'slug' => 'lead-times']);
    $faq = Faq::create(['faq_category_id' => $faqCat->id, 'question' => 'Q?', 'answer' => 'A.']);
    $galCat = GalleryCategory::create(['name' => 'Exterior', 'slug' => 'exterior']);
    $gal = Gallery::create(['gallery_category_id' => $galCat->id, 'image' => '/uploads/gallery/x.webp']);
    $zone = FloorZone::create(['name' => 'Master Suite', 'dims' => '3.2 m × 3.0 m', 'status' => true]);
    $product = Product::create(['name' => 'P2', 'slug' => 'p2', 'model_code' => 'M-2']);
    $dl = Download::create(['title' => 'Lookbook', 'file' => '/uploads/downloads/x.pdf', 'format' => 'PDF Spec', 'product_id' => $product->id]);
    $enq = Enquiry::create(['name' => 'Jane', 'email' => 'jane@example.com', 'product_id' => $product->id, 'source_page' => 'details']);

    expect($faq->category->id)->toBe($faqCat->id)
        ->and($gal->category->id)->toBe($galCat->id)
        ->and($dl->product->id)->toBe($product->id)
        ->and($enq->product->id)->toBe($product->id)
        ->and($zone->status)->toBeTrue();
});

test('page seo seeds new static keys', function () {
    $admin = adminUser();
    $this->actingAs($admin)->get(route('page-seo.index'))->assertOk();

    $keys = PageSeo::pluck('page_key')->all();
    foreach (['home', 'about', 'collections', 'custom-build', 'gallery', 'downloads', 'contact', 'privacy', 'terms'] as $key) {
        expect($keys)->toContain($key);
    }
});

test('admin product and enquiry pages render', function () {
    $admin = adminUser();
    $product = Product::create(['name' => 'P3', 'slug' => 'p3', 'model_code' => 'M-3']);

    $this->actingAs($admin)->get(route('products.index'))->assertOk();
    $this->actingAs($admin)->get(route('products.manage', $product->id))->assertOk();
    $this->actingAs($admin)->get(route('enquiries.index'))->assertOk();
    $this->actingAs($admin)->get(route('faqs.index'))->assertOk();
    $this->actingAs($admin)->get(route('galleries.index'))->assertOk();
    $this->actingAs($admin)->get(route('downloads.index'))->assertOk();
});

test('product store accepts glb model and youtube url', function () {
    $admin = adminUser();
    $file = UploadedFile::fake()->create('villa.glb', 120, 'model/gltf-binary');

    $res = $this->actingAs($admin)->post(route('products.store'), [
        'name' => 'GLB Villa', 'model_code' => 'GLB-01',
        'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'model_3d' => $file,
    ])->assertOk()->json();

    $product = Product::find($res['id']);
    expect($product->model_3d)->toStartWith('/uploads/products/3d/')->toEndWith('.glb');
    expect(file_exists(public_path($product->model_3d)))->toBeTrue();
    @unlink(public_path($product->model_3d));

    $this->actingAs($admin)->post(route('products.store'), [
        'name' => 'Bad Model', 'model_code' => 'BAD-01',
        'model_3d' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
    ])->assertStatus(422);
});

test('company details page exposes hidden fields and saves them', function () {
    $admin = adminUser();

    $html = $this->actingAs($admin)->get(route('admin.companyDetails'))->assertOk()->getContent();
    foreach (['business_name', 'email2', 'phone3', 'address2', 'website', 'tawkto', 'google_analytics_id', 'facebook_pixel_id', 'vat_number', 'footer_content'] as $field) {
        expect($html)->toContain('name="'.$field.'"');
    }

    $this->actingAs($admin)->post(route('admin.companyDetails'), [
        'company_name' => 'OriginSpaces', 'website' => 'https://example.com',
        'phone3' => '+44111', 'google_analytics_id' => 'G-TEST123', 'vat_number' => 'GB123',
    ])->assertRedirect();

    $data = CompanyDetails::first();
    expect($data->website)->toBe('https://example.com')
        ->and($data->google_analytics_id)->toBe('G-TEST123')
        ->and($data->vat_number)->toBe('GB123');
});

test('admin pages have no broken nested script tags', function () {
    $admin = adminUser();

    foreach (['products.index', 'allcategory', 'faqs.index', 'galleries.index', 'downloads.index'] as $route) {
        $html = $this->actingAs($admin)->get(route($route))->assertOk()->getContent();
        expect($html)->not->toContain("<script>\n<script>");
    }
});

test('every admin page renders', function () {
    $admin = adminUser();

    foreach ([
        'admin.dashboard', 'admin.companyDetails', 'allcategory',
        'slider.index', 'testimonial.index', 'page-seo.index',
        'admin.contacts.index', 'faq-categories.index',
        'gallery-categories.index', 'admin.profile',
    ] as $route) {
        $this->actingAs($admin)->get(route($route))->assertOk($route);
    }
});

test('category update persists SEO and video via HTTP', function () {
    $admin = adminUser();
    $cat = Category::create(['name' => 'Pods', 'slug' => 'pods']);

    $this->actingAs($admin)->post(route('category.update'), [
        'codeid' => $cat->id, 'name' => 'Pods',
        'video_url' => 'https://example.com/cat.mp4',
        'meta_title' => 'Pods UK', 'meta_description' => 'D',
        'meta_keywords' => 'pods', 'parent_id' => null,
    ])->assertOk();

    $cat->refresh();
    expect($cat->video_url)->toBe('https://example.com/cat.mp4')
        ->and($cat->meta_title)->toBe('Pods UK');
});

test('option quick-edit with name only keeps other fields', function () {
    $admin = adminUser();
    $product = Product::create(['name' => 'P4', 'slug' => 'p4', 'model_code' => 'M-4']);
    $opt = ProductOption::create([
        'product_id' => $product->id, 'group' => 'finish', 'name' => 'Oak',
        'subtitle' => 'Natural matt', 'price_delta' => 1500,
        'swatch_color' => '#5A4636', 'is_default' => true,
    ]);

    // manage blade quick-edit sends name only
    $this->actingAs($admin)->post(route('product-options.update', $opt->id), [
        'name' => 'Smoked Oak',
    ])->assertOk();

    $opt->refresh();
    expect($opt->name)->toBe('Smoked Oak')
        ->and($opt->subtitle)->toBe('Natural matt')
        ->and((float) $opt->price_delta)->toBe(1500.0)
        ->and($opt->swatch_color)->toBe('#5A4636')
        ->and($opt->is_default)->toBeTrue();
});

test('only one default option per group', function () {
    $admin = adminUser();
    $product = Product::create(['name' => 'P5', 'slug' => 'p5', 'model_code' => 'M-5']);
    $first = ProductOption::create([
        'product_id' => $product->id, 'group' => 'config', 'name' => 'A', 'is_default' => true,
    ]);

    $this->actingAs($admin)->post(route('product-options.store', $product->id), [
        'group' => 'config', 'name' => 'B', 'is_default' => 1,
    ])->assertOk();

    expect($first->fresh()->is_default)->toBeFalse();
});

test('product sort list and update work', function () {
    $admin = adminUser();
    $a = Product::create(['name' => 'SA', 'slug' => 'sa', 'model_code' => 'S-A', 'sort_order' => 0]);
    $b = Product::create(['name' => 'SB', 'slug' => 'sb', 'model_code' => 'S-B', 'sort_order' => 1]);

    $this->actingAs($admin)->get(route('products.sortList'))->assertOk()->assertJsonCount(2);
    $this->actingAs($admin)->post(route('products.sortUpdate'), [
        'ids' => [$b->id, $a->id],
    ])->assertOk();

    expect($a->fresh()->sort_order)->toBe(1)->and($b->fresh()->sort_order)->toBe(0);
});

test('product child CRUD roundtrip', function () {
    $admin = adminUser();
    $product = Product::create(['name' => 'P6', 'slug' => 'p6', 'model_code' => 'M-6']);

    // image requires a file
    $this->actingAs($admin)->post(route('product-images.store', $product->id), [])
        ->assertStatus(302)->assertInvalid('image');

    $this->actingAs($admin)->post(route('product-materials.store', $product->id), ['name' => 'Steel'])
        ->assertOk();
    $mat = $product->materials()->firstOrFail();
    $this->actingAs($admin)->post(route('product-materials.update', $mat->id), ['name' => 'Steel X'])
        ->assertOk();
    $this->actingAs($admin)->delete(route('product-materials.delete', $mat->id))->assertOk();

    $this->actingAs($admin)->post(route('product-specs.store', $product->id), ['point' => 'Fast build'])
        ->assertOk();
    $this->actingAs($admin)->get(route('product-specs.list', $product->id))->assertOk();

    $this->actingAs($admin)->post(route('product-tech-specs.store', $product->id), [
        'label' => 'U-Value', 'value' => '0.16',
    ])->assertOk();
    $tech = $product->techSpecs()->where('label', 'U-Value')->firstOrFail();
    $this->actingAs($admin)->delete(route('product-tech-specs.delete', $tech->id))->assertOk();

    // documents require a file
    $this->actingAs($admin)->post(route('product-documents.store', $product->id), ['title' => 'X'])
        ->assertStatus(302)->assertInvalid('file');

    // downloads require a file
    $this->actingAs($admin)->post(route('downloads.store'), [
        'title' => 'Lookbook', 'format' => 'PDF Spec',
    ])->assertStatus(302)->assertInvalid('file');
});

test('supporting module toggles flip status', function () {
    $admin = adminUser();
    $zoneProduct = Product::create(['name' => 'Zone Villa', 'slug' => 'zone-villa', 'model_code' => 'ZV-1']);
    $zone = FloorZone::create(['name' => 'Z', 'status' => true, 'product_id' => $zoneProduct->id]);
    $faqCat = FaqCategory::create(['name' => 'FC', 'slug' => 'fc', 'status' => true]);
    $faq = Faq::create(['faq_category_id' => $faqCat->id, 'question' => 'Q', 'answer' => 'A', 'status' => true]);
    $galCat = GalleryCategory::create(['name' => 'GC', 'slug' => 'gc', 'status' => true]);
    $gal = Gallery::create(['gallery_category_id' => $galCat->id, 'image' => '/x.webp', 'status' => true]);
    $enq = Enquiry::create(['name' => 'N', 'email' => 'n@example.com', 'status' => true]);

    foreach ([
        [route('product-floor-zones.toggleStatus', $zone->id), $zone],
        [route('faq-categories.toggleStatus'), $faqCat],
        [route('faqs.toggleStatus'), $faq],
        [route('gallery-categories.toggleStatus'), $galCat],
        [route('galleries.toggleStatus'), $gal],
        [route('enquiries.toggleStatus'), $enq],
        [route('admin.contacts.toggleStatus'), Contact::create(['name' => 'C', 'subject' => 'S', 'message' => 'Hi', 'status' => true])],
    ] as [$url, $model]) {
        $this->actingAs($admin)->post($url, ['id' => $model->id])->assertOk();
        expect($model->fresh()->status)->toBeFalse();
    }
});

test('page seo update persists', function () {
    $admin = adminUser();
    $this->actingAs($admin)->get(route('page-seo.index'))->assertOk();
    $seo = PageSeo::where('page_key', 'custom-build')->firstOrFail();

    $this->actingAs($admin)->post(route('page-seo.update'), [
        'id' => $seo->id, 'meta_title' => 'Custom Build UK',
    ])->assertOk();

    expect($seo->fresh()->meta_title)->toBe('Custom Build UK');
});

test('downloads table renders rows without files', function () {
    Download::create(['title' => 'No file yet', 'file' => null, 'format' => 'PDF Spec', 'status' => true]);

    $response = $this->actingAs(adminUser())->get(route('downloads.index'), ['X-Requested-With' => 'XMLHttpRequest']);

    $response->assertOk();
});

test('downloads update can remove existing file', function () {
    $dl = Download::create(['title' => 'With file', 'file' => '/uploads/downloads/x.pdf', 'format' => 'PDF Spec', 'status' => true]);

    $this->actingAs(adminUser())->post(route('downloads.update'), ['id' => $dl->id, 'title' => 'With file', 'format' => 'PDF Spec', 'remove_file' => '1'])->assertOk();

    expect($dl->fresh()->file)->toBeNull();
});

test('products table filters by category', function () {
    $admin = adminUser();
    $a = Category::create(['name' => 'Filter A', 'slug' => 'filter-a']);
    $b = Category::create(['name' => 'Filter B', 'slug' => 'filter-b']);
    Product::create(['name' => 'PA', 'slug' => 'pa', 'model_code' => 'PA-1', 'category_id' => $a->id]);
    Product::create(['name' => 'PB', 'slug' => 'pb', 'model_code' => 'PB-1', 'category_id' => $b->id]);

    $ajax = ['draw' => 1, 'start' => 0, 'length' => 10, 'X-Requested-With' => 'XMLHttpRequest'];

    $all = $this->actingAs($admin)->get(route('products.index'), $ajax)->assertOk()->json();
    expect($all['recordsTotal'])->toBe(2);

    $filtered = $this->actingAs($admin)
        ->get(route('products.index', ['category_id' => $a->id]), $ajax)->assertOk()->json();
    expect($filtered['recordsFiltered'])->toBe(1)
        ->and($filtered['data'][0]['name'])->toBe('PA');
});

test('product slugs are auto-generated and deduped', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post(route('products.store'), [
        'name' => 'Café Pod', 'model_code' => 'MD-1',
    ])->assertOk();
    $this->actingAs($admin)->post(route('products.store'), [
        'name' => 'Cafe Pod', 'model_code' => 'MD-1X',
    ])->assertOk();

    // 'Café Pod MD-1' and 'Cafe Pod MD-1X' must not collide; second gets a suffix
    $slugs = Product::orderBy('id')->pluck('slug')->all();
    expect($slugs[0])->toBe('cafe-pod-md-1')->and($slugs[1])->toBe('cafe-pod-md-1x');

    // Slug is backend-only: a posted slug is ignored, rename regenerates it
    $first = Product::where('slug', 'cafe-pod-md-1')->firstOrFail();
    $this->actingAs($admin)->post(route('products.update'), [
        'codeid' => $first->id, 'name' => 'Renamed Pod', 'model_code' => 'MD-1',
        'slug' => 'my-custom-slug',
    ])->assertOk();
    expect($first->fresh()->slug)->toBe('renamed-pod-md-1');
});

test('category and taxonomy slugs are auto-generated and deduped', function () {
    $admin = adminUser();

    // Accent-variant names slugify identically: second gets an auto suffix, no 500
    $this->actingAs($admin)->post(route('category.store'), ['name' => 'Café'])->assertOk();
    $this->actingAs($admin)->post(route('category.store'), ['name' => 'Cafe'])->assertOk();
    expect(Category::where('name', 'Café')->firstOrFail()->slug)->toBe('cafe')
        ->and(Category::where('name', 'Cafe')->firstOrFail()->slug)->toBe('cafe-2');

    // Rename regenerates the slug from the new name
    $first = Category::where('slug', 'cafe')->firstOrFail();
    $this->actingAs($admin)->post(route('category.update'), [
        'codeid' => $first->id, 'name' => 'Coffee Houses',
    ])->assertOk();
    expect($first->fresh()->slug)->toBe('coffee-houses');

    // FAQ + gallery categories dedupe on store the same way
    $this->actingAs($admin)->post(route('faq-categories.store'), ['name' => 'Lead Times'])->assertOk();
    $this->actingAs($admin)->post(route('faq-categories.store'), ['name' => 'Lead Times!!'])->assertOk();
    expect(FaqCategory::where('name', 'Lead Times!!')->firstOrFail()->slug)->toBe('lead-times-2');

    $this->actingAs($admin)->post(route('gallery-categories.store'), ['name' => 'Exterior'])->assertOk();
    $this->actingAs($admin)->post(route('gallery-categories.store'), ['name' => 'Exterior'])->assertStatus(302)->assertInvalid('name');

    expect(FaqCategory::where('name', 'Lead Times')->firstOrFail()->slug)->toBe('lead-times')
        ->and(GalleryCategory::where('name', 'Exterior')->firstOrFail()->slug)->toBe('exterior');
});
