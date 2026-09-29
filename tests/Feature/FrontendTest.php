<?php

use App\Models\Category;
use App\Models\Contact;
use App\Models\OptionGroup;
use App\Models\OptionValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedGrocery(): Product
{
    $cat = Category::create(['name' => 'Fresh Meat', 'slug' => 'fresh-meat']);
    $group = OptionGroup::create(['name' => 'Pack Size', 'slug' => 'pack-size', 'type' => 'buttons', 'sort_order' => 0]);
    $v500 = OptionValue::create(['option_group_id' => $group->id, 'label' => '500g', 'slug' => '500g', 'sort_order' => 0]);
    $cat->optionGroups()->sync([$group->id => ['sort_order' => 0]]);

    $product = Product::create([
        'category_id' => $cat->id, 'name' => 'Lamb Leg', 'slug' => 'lamb-leg',
        'tagline' => 'Grass-fed lamb', 'highlights' => "Grass fed\nFresh",
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id, 'sku' => 'LAMB-500', 'mrp' => 12.99, 'offer_price' => 10.99,
        'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
    ]);
    $variant->values()->sync([$v500->id]);
    $product->extraAttributes()->create(['label' => 'Storage', 'value' => 'Keep chilled', 'sort_order' => 0]);

    return $product;
}

test('all public pages render with layout shell', function () {
    seedGrocery();

    foreach ([
        '/', '/about', '/collections', '/product/lamb-leg', '/offers',
        '/gallery', '/bag', '/checkout', '/faq',
        '/contact', '/privacy-policy', '/terms-of-service', '/login',
    ] as $uri) {
        $this->get($uri)->assertOk($uri);
    }
});

test('collections lists the grocery product with its price', function () {
    seedGrocery();

    $this->get('/collections?category=fresh-meat')
        ->assertOk()
        ->assertSee('Fresh Meat', false)
        ->assertSee('Lamb Leg', false);

    $this->get('/collections?category=Fresh+Meat')
        ->assertOk()
        ->assertSee('Lamb Leg', false);
});

test('details page carries grocery data and variant matrix', function () {
    seedGrocery();

    $this->get('/product/lamb-leg')
        ->assertOk()
        ->assertSee('Grass-fed lamb', false)
        ->assertSee('Grass fed', false)
        ->assertSee('LAMB-500', false);
});

test('unknown product slug 404s', function () {
    $this->get('/product/nope')->assertNotFound();
});

test('shop search, category filter and sorting work server-side', function () {
    seedGrocery();

    $this->get('/collections?category=fresh-meat')->assertOk()->assertSee('Lamb Leg', false);
    $this->get('/collections?q=lamb')->assertOk()->assertSee('Lamb Leg', false);
    $this->get('/collections?q=zzz-no-match')->assertOk()->assertSee('Nothing found', false);
    $this->get('/collections?sort=price_asc')->assertOk()->assertSee('Lamb Leg', false);
    $this->get('/collections?sort=bogus')->assertOk()->assertSee('Lamb Leg', false);
});

test('offers page lists only discounted variants with save badges', function () {
    seedGrocery();

    $html = $this->get('/offers')->assertOk()->getContent();
    expect($html)->toContain('LAMB-500')
        ->toContain('Save 15%');
});

test('details page exposes variant picker data for live pricing', function () {
    seedGrocery();

    $html = $this->get('/product/lamb-leg')->assertOk()->getContent();
    expect($html)->toContain('data-variant-picker')
        ->toContain('data-current-price')
        ->toContain('500g')
        ->toContain('Storage')
        ->toContain('Keep chilled');
});

test('frontend scripts stay spa-safe and icons render server-side', function () {
    seedGrocery();

    foreach (glob(resource_path('views/frontend/*.blade.php')) as $file) {
        $html = file_get_contents($file);
        expect($html)->not->toContain('DOMContentLoaded', basename($file).' must init directly, DOMContentLoaded never fires after SPA navigation');
        expect(preg_match('/^    (const|let) (?=[A-Za-z_$])/m', $html))->toBe(0, basename($file).' must not use top-level const/let, the engine re-executes scripts on every navigation');
    }

    $js = file_get_contents(public_path('resources/frontend/js/egf.js'));
    expect($js)->not->toContain("addEventListener('DOMContentLoaded'")
        ->not->toContain('addEventListener("DOMContentLoaded"')
        ->not->toContain('const CART_KEY');

    foreach (['/', '/collections', '/product/lamb-leg', '/contact'] as $uri) {
        $this->get($uri)->assertOk()
            ->assertSee('lucide lucide-search', false)
            ->assertDontSee('<i data-lucide="search">', false);
    }
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
