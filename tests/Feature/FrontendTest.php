<?php

use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\Contact;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Gallery;
use App\Models\GalleryCategory;
use App\Models\OptionGroup;
use App\Models\OptionValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
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
        '/', '/about', '/shop', '/product/lamb-leg',
        '/gallery', '/bag', '/checkout', '/faq', '/loyalty', '/delivery',
        '/contact', '/privacy-policy', '/terms-of-service', '/refund-policy', '/login',
    ] as $uri) {
        $this->get($uri)->assertOk($uri);
    }

    $this->get('/offers')->assertRedirect('/shop?only_offers=1');
    $this->get('/collections')->assertNotFound();
    $this->get('/shop/offers')->assertOk();
});

test('shop lists the grocery product with its price', function () {
    seedGrocery();

    $this->get('/shop/fresh-meat')
        ->assertOk()
        ->assertSee('Fresh Meat', false)
        ->assertSee('Lamb Leg', false);

    $this->get('/shop?category=fresh-meat')->assertRedirect('/shop/fresh-meat');
    $this->get('/shop?category=Fresh+Meat')->assertRedirect('/shop/fresh-meat');
    $this->get('/shop/nope')->assertNotFound();
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

    $this->get('/shop/fresh-meat')->assertOk()->assertSee('Lamb Leg', false);
    $this->get('/shop?q=lamb')->assertOk()->assertSee('Lamb Leg', false);
    $this->get('/shop?q=zzz-no-match')->assertOk()->assertSee('Nothing found', false);
    $this->get('/shop?sort=price_asc')->assertOk()->assertSee('Lamb Leg', false);
    $this->get('/shop?sort=bogus')->assertOk()->assertSee('Lamb Leg', false);
});

test('shop offers filter lists only discounted products with save badges', function () {
    seedGrocery();

    $plain = Product::create([
        'category_id' => Category::firstOrFail()->id, 'name' => 'Plain Rice', 'slug' => 'plain-rice',
        'tagline' => 'No offer here',
    ]);
    ProductVariant::create([
        'product_id' => $plain->id, 'sku' => 'PLAIN-01', 'mrp' => 4.00, 'offer_price' => null,
        'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
    ]);

    $all = $this->get('/shop')->assertOk()->getContent();
    expect($all)->toContain('/product/lamb-leg')
        ->toContain('/product/plain-rice');

    $html = $this->get('/shop/offers')->assertOk()->getContent();
    expect($html)->toContain('LAMB-500')
        ->toContain('Save 15%')
        ->toContain('Offers ×')
        ->not->toContain('/product/plain-rice');

    $this->get('/shop?only_offers=1')->assertRedirect('/shop/offers');
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
        ->not->toContain('const CART_KEY')
        ->toContain('paused');

    foreach (['/', '/shop', '/product/lamb-leg', '/contact'] as $uri) {
        $this->get($uri)->assertOk()
            ->assertSee('lucide lucide-search', false)
            ->assertDontSee('<i data-lucide="search">', false);
    }
});

test('shop groups categories into parents with child chips', function () {
    $base = seedGrocery();
    $lamb = Category::create(['name' => 'Lamb', 'slug' => 'lamb', 'parent_id' => $base->category_id]);

    $chops = Product::create([
        'category_id' => $lamb->id, 'name' => 'Lamb Chops', 'slug' => 'lamb-chops',
        'tagline' => 'Grill-ready chops',
    ]);
    ProductVariant::create([
        'product_id' => $chops->id, 'sku' => 'CHOP-500', 'mrp' => 12.99, 'offer_price' => 10.99,
        'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
    ]);

    // Parent page includes the descendant product and renders child chips.
    $parent = $this->get('/shop/fresh-meat')->assertOk()->getContent();
    expect($parent)->toContain('/product/lamb-chops')
        ->toContain('/product/lamb-leg')
        ->toContain('child-chip');

    // Child page narrows to its own products.
    $kid = $this->get('/shop/lamb')->assertOk()->getContent();
    expect($kid)->toContain('/product/lamb-chops')
        ->not->toContain('/product/lamb-leg');
});

test('home category cards show parents only with subtree counts', function () {
    $base = seedGrocery();
    Category::create(['name' => 'Lamb', 'slug' => 'lamb', 'parent_id' => $base->category_id]);

    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('/shop/fresh-meat')
        // Child categories appear nowhere — home grid and footer are parents-only.
        ->and(substr_count($html, '/shop/lamb'))->toBe(0);
});

test('gallery items carry lightbox data', function () {
    $gc = GalleryCategory::create(['name' => 'Market', 'slug' => 'market', 'status' => true, 'sort_order' => 0]);
    Gallery::create([
        'gallery_category_id' => $gc->id, 'image' => 'https://example.com/a.jpg',
        'caption' => 'Fresh morning', 'status' => true, 'sort_order' => 0,
    ]);

    $html = $this->get('/gallery')->assertOk()->getContent();
    expect($html)->toContain('data-gallery-item')
        ->toContain('data-full="https://example.com/a.jpg"');
});

test('account portal renders sidebar tabs and panels', function () {
    $user = User::create([
        'name' => 'Portal', 'email' => 'portal@example.com',
        'password' => bcrypt('password'), 'user_type' => 0,
    ]);

    $html = $this->actingAs($user)->get(route('account'))->assertOk()->getContent();
    expect($html)->toContain('data-portal-tab="orders"')
        ->toContain('data-portal-tab="loyalty"')
        ->toContain('data-portal-tab="details"')
        ->toContain('data-portal-tab="password"')
        ->toContain('data-portal-panel="orders"')
        ->toContain('egfPortalInit');
});

test('shoppers can favourite, list and move favourites to the bag', function () {
    $product = seedGrocery();
    $variant = $product->variants()->firstOrFail();
    $user = User::create([
        'name' => 'Favs', 'email' => 'favs@example.com',
        'password' => bcrypt('password'), 'user_type' => 0,
    ]);

    $this->actingAs($user)->postJson(route('favourites.toggle'), ['product_id' => $product->id])
        ->assertOk()->assertJson(['favourited' => true]);

    $this->actingAs($user)->get(route('favourites'))->assertOk()->assertSee('Lamb Leg', false);

    $this->actingAs($user)->post(route('favourites.move-all'))->assertRedirect(route('bag'));
    expect(session('bag'))->toBe([$variant->id => 1]);

    $this->actingAs($user)->postJson(route('favourites.toggle'), ['product_id' => $product->id])
        ->assertOk()->assertJson(['favourited' => false]);

    $this->actingAs($user)->get(route('favourites'))->assertOk()->assertSee('Nothing saved yet', false);
});

test('guests are sent to sign in for favourites', function () {
    $this->get(route('favourites'))->assertRedirect(route('login'));
    $this->postJson(route('favourites.toggle'), ['product_id' => 1])->assertUnauthorized();
});

test('shop price range narrows by cheapest variant', function () {
    seedGrocery();

    $this->get('/shop?min_price=10&max_price=11')->assertOk()->assertSee('/product/lamb-leg', false);
    $this->get('/shop?min_price=11')->assertOk()->assertSee('Nothing found', false);
    $this->get('/shop?max_price=5')->assertOk()->assertSee('Nothing found', false);
});

test('shop pages the grid with a load more button', function () {
    $cat = Category::create(['name' => 'Bulk', 'slug' => 'bulk']);
    for ($i = 1; $i <= 25; $i++) {
        $p = Product::create([
            'category_id' => $cat->id, 'name' => "Bulk Item $i", 'slug' => "bulk-item-$i",
            'tagline' => 'Bulk',
        ]);
        ProductVariant::create([
            'product_id' => $p->id, 'sku' => "BULK-$i", 'mrp' => 5.00, 'offer_price' => null,
            'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
        ]);
    }

    $first = $this->get('/shop')->assertOk()->getContent();
    expect(substr_count($first, '/product/bulk-item-'))->toBe(48)
        ->and($first)->toContain('Load more')
        ->toContain('Showing 24 of 25');

    $second = $this->get('/shop?page=2')->assertOk()->getContent();
    expect(substr_count($second, '/product/bulk-item-'))->toBe(50)
        ->and($second)->toContain('/product/bulk-item-1')
        ->and($second)->toContain('/product/bulk-item-25')
        ->and($second)->not->toContain('Load more');

    $this->get('/shop?page=bogus')->assertOk()->assertSee('Showing 24 of 25', false);
});

test('shop exposes capped price bounds for the slider', function () {
    seedGrocery();

    $salt = Product::create([
        'category_id' => Category::firstOrFail()->id, 'name' => 'Sea Salt', 'slug' => 'sea-salt',
        'tagline' => 'Flaky salt',
    ]);
    ProductVariant::create([
        'product_id' => $salt->id, 'sku' => 'SALT-01', 'mrp' => 2.49, 'offer_price' => null,
        'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0,
    ]);

    $html = $this->get('/shop')->assertOk()->getContent();
    expect($html)->toContain('data-price-slider')
        ->toContain('data-floor="2"')
        ->toContain('data-ceil="11"');
});

test('details page renders option groups as buttons', function () {
    seedGrocery();

    $html = $this->get('/product/lamb-leg')->assertOk()->getContent();
    expect($html)->toContain('pack-opt')
        ->not->toContain('data-group-select');
});

test('native selects stay server-rendered for pretty-select enhancement', function () {
    $product = seedGrocery();

    $this->get('/shop')->assertOk()->assertSee('<select class="sort-select"', false);

    $variant = $product->variants()->firstOrFail();
    $this->postJson(route('bag.add'), ['variant_id' => $variant->id, 'qty' => 1])->assertOk();
    $this->get('/checkout')->assertOk()->assertSee('id="co-slot"', false)->assertSee('id="co-date"', false);
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

test('footer shows company social icons and pages show the map', function () {
    CompanyDetails::firstOrCreate()->update([
        'facebook' => 'https://facebook.com/egf',
        'google_map' => '<iframe src="https://maps.example.com"></iframe>',
    ]);

    $home = $this->get('/')->assertOk()->getContent();
    expect($home)->toContain('social-row')
        ->toContain('https://facebook.com/egf')
        ->toContain('maps.example.com')
        ->toContain('data-cookie-banner')
        ->toContain('Accept all');

    $contact = $this->get('/contact')->assertOk()->getContent();
    expect($contact)->toContain('maps.example.com')
        ->toContain('contact-grid');
});

test('faq page renders a grouped accordion', function () {
    $c = FaqCategory::create(['name' => 'Orders', 'slug' => 'orders', 'status' => true, 'sort_order' => 0]);
    Faq::create([
        'faq_category_id' => $c->id, 'question' => 'Is there a minimum order?',
        'answer' => 'Yes, £15 for delivery.', 'badge' => 'Orders',
        'status' => true, 'sort_order' => 0,
    ]);

    $html = $this->get('/faq')->assertOk()->getContent();
    expect($html)->toContain('faq-item')
        ->toContain('Is there a minimum order?')
        ->toContain('Yes, £15 for delivery.');
});

test('loyalty and delivery pages show live rates and minimums', function () {
    $loyalty = $this->get('/loyalty')->assertOk()->getContent();
    expect($loyalty)->toContain('How it works')
        ->toContain('point per £1');

    $delivery = $this->get('/delivery')->assertOk()->getContent();
    expect($delivery)->toContain('Minimum order')
        ->toContain('£15.00')
        ->toContain('£50.00');
});
