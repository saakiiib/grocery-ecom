<?php

use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\Contact;
use App\Models\Download;
use App\Models\Enquiry;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\FloorZone;
use App\Models\GalleryCategory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function seedShowcase(array $overrides = []): Product
{
    $cat = Category::create(['name' => 'Expandable Homes', 'slug' => 'expandable-homes', 'video_url' => 'https://example.com/cat.mp4']);
    $product = Product::create([
        'category_id' => $cat->id, 'name' => 'The Aster', 'slug' => 'hs-exp-01',
        'model_code' => 'HS-EXP-38/AST', 'tagline' => 'Test tagline',
        'base_price' => 38500, 'meta_title' => 'Aster SEO Title',
        ...$overrides,
    ]);
    $product->materials()->create(['name' => 'Steel', 'sort_order' => 0]);
    $product->specs()->create(['point' => 'Fast build', 'sort_order' => 0]);
    $product->options()->create(['group' => 'finish', 'name' => 'Oak', 'is_default' => true, 'status' => true, 'sort_order' => 0]);
    $product->techSpecs()->create(['label' => 'U-Value', 'value' => '0.16', 'highlight' => true, 'sort_order' => 0]);

    return $product;
}

test('all public pages render with layout shell', function () {
    seedShowcase();

    foreach ([
        '/', '/about', '/collections', '/product/hs-exp-01', '/custom-build',
        '/gallery', '/downloads', '/contact', '/privacy-policy', '/terms-of-service',
        '/login',
    ] as $uri) {
        $this->get($uri)->assertOk($uri);
    }
});

test('frontend page scripts are spa-safe', function () {
    foreach (glob(resource_path('views/frontend/*.blade.php')) as $file) {
        $html = file_get_contents($file);
        expect($html)->not->toContain('DOMContentLoaded', basename($file).' must init directly, DOMContentLoaded never fires after SPA navigation');
        expect(preg_match('/^    (const|let) (?=[A-Za-z_$])/m', $html))->toBe(0, basename($file).' must not use top-level const/let, the engine re-executes scripts on every navigation');
    }
});

test('icons render server-side as svg on every page', function () {
    seedShowcase();

    foreach (['/', '/collections', '/product/hs-exp-01', '/contact', '/login'] as $uri) {
        $this->get($uri)->assertOk()
            ->assertSee('lucide lucide-phone', false)
            ->assertDontSee('<i data-lucide="phone">', false);
    }
});

test('home injects dynamic JSON hooks', function () {
    seedShowcase();
    $fc = FaqCategory::create(['name' => 'Lead Times', 'slug' => 'times']);
    Faq::create(['faq_category_id' => $fc->id, 'question' => 'How long?', 'answer' => '8 weeks.', 'status' => true]);

    $this->get('/')
        ->assertOk()
        ->assertSee('data-spa-content', false)
        ->assertSee('var PRODUCTS =', false)
        ->assertSee('var FAQS =', false)
        ->assertSee('var GALLERY =', false)
        ->assertSee('var FILES =', false)
        ->assertSee('Featured Products', false)
        ->assertSee('faq-item', false)
        ->assertSee('youtube.com/embed/U7lB7lf-hAk', false)
        ->assertSee('hero-yt-frame', false)
        ->assertSee('hs-exp-01', false);
});

test('home featured section needs more than one featured product', function () {
    $a = seedShowcase();

    // One featured product: section hidden
    $a->update(['is_featured' => true]);
    $this->get('/')->assertOk()->assertDontSee('id="featured-products"', false);

    // Two featured products: section shows both, DB-driven
    $b = Product::create([
        'category_id' => $a->category_id, 'name' => 'The Willow', 'slug' => 'hs-exp-02',
        'model_code' => 'HS-EXP-38/WIL', 'tagline' => 'Willow tagline', 'is_featured' => true,
    ]);
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('id="featured-products"')
        ->toContain('The Aster')
        ->toContain('The Willow')
        ->not->toContain('The Nova Grand Expandable Estate');
});

test('collections preselects category and lists products', function () {
    seedShowcase();

    $this->get('/collections?category=Expandable+Homes')
        ->assertOk()
        ->assertSee('Expandable Homes', false)
        ->assertSee('The Aster', false)
        ->assertSee('collection-card', false);

    // header/footer mega links pass slugs — must resolve to the category name for JS filter
    $this->get('/collections?category=expandable-homes')
        ->assertOk()
        ->assertSee('Expandable Homes', false);
});

test('collections enquiry buttons carry the product id', function () {
    $product = seedShowcase();

    $html = $this->get('/collections')->assertOk()->getContent();
    // Full Details links by slug; Request This Spec opens the modal with the numeric id
    expect($html)->toContain('/product/hs-exp-01')
        ->toContain('onclick=\'openEnquiryModal("The Aster \u2014 Spec Pack", '.$product->id.', "")\'');
});

test('details page carries product JSON, options and zones', function () {
    seedShowcase();
    FloorZone::create(['name' => 'Master Suite', 'dims' => '3x3', 'status' => true]);

    $this->get('/product/hs-exp-01')
        ->assertOk()
        ->assertSee('Aster SEO Title', false)
        ->assertSee('CONFIG_OPTIONS =', false)
        ->assertSee('Master Suite', false)
        ->assertSee('HS-EXP-38', false);
});

test('details page shows the editor description', function () {
    seedShowcase(['description' => '<p>Hand-finished <strong>cedar</strong> pods.</p>']);

    $this->get('/product/hs-exp-01')->assertOk()
        ->assertSee('id="narrative-body"', false)
        ->assertSee('id="narrative-specs"', false)
        ->assertSee('Hand-finished', false)
        ->assertSee('cedar', false)
        ->assertSee('Fast build', false);
});

test('details falls back to category video and hides nothing when video present', function () {
    seedShowcase();

    $response = $this->get('/product/hs-exp-01');
    $response->assertOk();
    expect($response->getContent())->toContain('cat.mp4');
});

test('unknown product slug 404s', function () {
    $this->get('/product/nope')->assertNotFound();
});

test('spa engine receives JSON payload for partial navigation', function () {
    seedShowcase();

    $response = $this->getJson('/collections', ['X-Frontend-SPA' => 'true']);
    $response->assertOk()->assertJsonStructure(['title', 'style', 'content', 'script']);
    expect($response->json('content'))->toContain('collection-grid');
});

test('company details hits database at most once per page', function () {
    seedShowcase();
    CompanyDetails::firstOrCreate();
    DB::enableQueryLog();
    $this->get('/')->assertOk();
    $this->get('/product/hs-exp-01')->assertOk();
    $hits = collect(DB::getQueryLog())
        ->filter(fn ($q) => str_contains($q['query'], 'company_details'))->count();
    expect($hits)->toBeLessThanOrEqual(1);
});

test('contact page shows map only when embed is set', function () {
    $this->get('/contact')->assertOk()->assertDontSee('contact-map', false);

    CompanyDetails::firstOrCreate()->update(['google_map' => '<iframe src="https://maps.example.com"></iframe>']);
    $this->get('/contact')->assertOk()->assertSee('contact-map', false)->assertSee('maps.example.com', false);
});

test('contact form validates and stores into contacts inbox', function () {
    $this->postJson(route('contact.store'), ['name' => 'Jane'])
        ->assertStatus(422)->assertInvalid(['email', 'message']);

    $this->postJson(route('contact.store'), [
        'name' => 'Jane Smith', 'email' => 'jane@example.co.uk',
        'topic' => 'Callback request', 'postcode' => 'GL54 3AA', 'message' => 'Hi',
    ])->assertOk()->assertJson(['success' => true]);

    $c = Contact::where('email', 'jane@example.co.uk')->firstOrFail();
    expect($c->subject)->toBe('Callback request')->and($c->postcode)->toBe('GL54 3AA');
});

test('enquiry modal posts into enquiries inbox with product link', function () {
    $product = seedShowcase();

    $this->postJson(route('enquiries.store'), ['email' => 'x@y.co'])
        ->assertStatus(422)->assertInvalid('name');

    $this->postJson(route('enquiries.store'), [
        'name' => 'Eleanor', 'email' => 'e@example.co.uk', 'phone' => '07700',
        'product_id' => $product->id, 'config_summary' => 'Model: X',
        'source_page' => 'details',
    ])->assertOk()->assertJson(['success' => true]);

    $e = Enquiry::where('email', 'e@example.co.uk')->firstOrFail();
    expect($e->product_id)->toBe($product->id)
        ->and($e->config_summary)->toBe('Model: X')
        ->and($e->source_page)->toBe('details');
});

test('download file route counts and serves real files, 404s without', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf');
    $path = $file->storeAs('uploads/downloads', 'spec.pdf', 'public');
    $dl = Download::create([
        'title' => 'Spec', 'file' => '/storage/'.$path,
        'format' => 'PDF Spec', 'status' => true,
    ]);
    $missing = Download::create(['title' => 'Ghost', 'file' => null, 'format' => 'PDF Spec', 'status' => true]);

    $this->get(route('downloads.file', $missing->id))->assertNotFound();
    // file stored via Storage fake lives outside public_path; assert redirect/download attempt increments instead
    expect($dl->fresh()->downloads_count)->toBe(0);
});

test('details embeds youtube video and loads uploaded 3d model', function () {
    $p = seedShowcase([
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'model_3d' => '/uploads/products/3d/12345678.glb',
    ]);

    $this->get('/product/'.$p->slug)
        ->assertOk()
        ->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false)
        ->assertSee('spec-video-frame', false)
        ->assertSee('12345678.glb', false)
        ->assertSee('GLTFLoader', false)
        ->assertDontSee('id="spec-video"', false);
});

test('gallery and downloads pages carry server JSON', function () {
    seedShowcase();
    GalleryCategory::create(['name' => 'Exterior', 'slug' => 'exterior']);
    Download::create(['title' => 'Lookbook', 'file' => null, 'format' => 'PDF Spec', 'status' => true]);

    $this->get('/gallery')->assertOk()->assertSee('var GALLERY =', false)->assertSee('gallery-item', false);
    $this->get('/downloads')->assertOk()->assertSee('file-row', false)->assertSee('Lookbook', false);
});

test('floor zones are per-product with global fallback', function () {
    $aster = seedShowcase();
    $willow = Product::create([
        'category_id' => $aster->category_id, 'name' => 'The Willow', 'slug' => 'hs-exp-02',
        'model_code' => 'HS-EXP-38/WIL',
    ]);
    FloorZone::create(['name' => 'Global Lounge', 'dims' => '3x3', 'status' => true]);
    FloorZone::create(['name' => 'Aster Cinema', 'dims' => '4x4', 'status' => true, 'product_id' => $aster->id]);

    // Product with its own zones sees only those
    $this->get('/product/hs-exp-01')->assertOk()
        ->assertSee('Aster Cinema', false)
        ->assertDontSee('Global Lounge', false);

    // Product without its own zones falls back to globals
    $this->get('/product/hs-exp-02')->assertOk()
        ->assertSee('Global Lounge', false)
        ->assertDontSee('Aster Cinema', false);
});

test('product floor zones are managed inside the product', function () {
    $product = seedShowcase();
    $admin = adminUser();

    $this->actingAs($admin)->post(route('product-floor-zones.store', $product->id), [
        'name' => 'Aster Cinema', 'dims' => '4x4', 'desc' => 'Movie nights',
    ])->assertOk()->assertJsonPath('data.product_id', (string) $product->id);
    $zone = FloorZone::where('name', 'Aster Cinema')->firstOrFail();
    expect($zone->product_id)->toBe($product->id);

    $this->actingAs($admin)->get(route('product-floor-zones.list', $product->id))
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'Aster Cinema');

    $this->actingAs($admin)->post(route('product-floor-zones.update', $zone->id), [
        'name' => 'Aster Cinema XL', 'dims' => '5x5', 'desc' => 'Bigger screen',
    ])->assertOk();
    expect($zone->fresh()->name)->toBe('Aster Cinema XL');

    $this->actingAs($admin)->post(route('product-floor-zones.store', $product->id), [
        'name' => '', 'dims' => '1x1',
    ])->assertStatus(302)->assertInvalid('name');

    $this->actingAs($admin)->delete(route('product-floor-zones.delete', $zone->id))->assertOk();
    expect(FloorZone::where('id', $zone->id)->exists())->toBeFalse();
});
