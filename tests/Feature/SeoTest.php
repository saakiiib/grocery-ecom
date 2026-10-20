<?php

use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\FlashSale;
use App\Models\PageSeo;
use App\Models\Product;
use App\Models\ProductRedirect;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Seo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seoCategory(array $attributes = []): Category
{
    return Category::create(array_merge([
        'name' => 'Asian Groceries',
        'slug' => 'asian-groceries',
        'status' => true,
    ], $attributes));
}

function seoProduct(Category $category, array $attributes = []): Product
{
    $product = Product::create(array_merge([
        'category_id' => $category->id,
        'name' => 'Basmati Rice 5kg',
        'slug' => 'basmati-rice-5kg',
        'status' => true,
    ], $attributes));

    ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'RICE-5KG',
        'mrp' => 12.99,
        'is_default' => true,
        'in_stock' => true,
        'status' => true,
        'sort_order' => 0,
    ]);

    return $product;
}

beforeEach(function () {
    CompanyDetails::firstOrCreate([], ['company_name' => 'Alam Mini Market']);
});

test('public pages render exactly one title tag', function () {
    $category = seoCategory();
    seoProduct($category);

    foreach (['/', '/shop', '/shop/asian-groceries', '/product/basmati-rice-5kg', '/about', '/delivery', '/faq'] as $uri) {
        $html = $this->get($uri)->assertOk()->getContent();
        expect(substr_count($html, '<title>'))->toBe(1, "duplicate title on {$uri}");
    }
});

test('category page uses its own meta title and description', function () {
    $category = seoCategory([
        'meta_title' => 'Asian Groceries Delivered in Northampton',
        'meta_description' => 'Shop fresh Asian groceries with same-day local delivery.',
        'description' => 'Rice, spices, noodles and more.',
    ]);
    seoProduct($category);

    $this->get('/shop/asian-groceries')
        ->assertOk()
        ->assertSee('<title>Asian Groceries Delivered in Northampton</title>', false)
        ->assertSee('content="Shop fresh Asian groceries with same-day local delivery."', false)
        ->assertSee('rel="canonical" href="'.route('shop.category', 'asian-groceries').'"', false)
        ->assertSee('Rice, spices, noodles and more.', false);
});

test('category without meta falls back to its description for the intro and its own canonical', function () {
    $category = seoCategory(['description' => 'Halal meat, rice and spices.']);
    seoProduct($category);

    $this->get('/shop/asian-groceries')
        ->assertOk()
        ->assertSee('Halal meat, rice and spices.', false)
        ->assertSee('rel="canonical" href="'.route('shop.category', 'asian-groceries').'"', false);
});

test('product page uses its own meta with sensible fallbacks and a canonical URL', function () {
    $category = seoCategory();
    $product = seoProduct($category, [
        'meta_title' => 'Buy Basmati Rice 5kg Online',
        'meta_description' => 'Premium aged basmati rice, delivered locally.',
    ]);

    $this->get('/product/basmati-rice-5kg')
        ->assertOk()
        ->assertSee('<title>Buy Basmati Rice 5kg Online</title>', false)
        ->assertSee('content="Premium aged basmati rice, delivered locally."', false)
        ->assertSee('rel="canonical" href="'.route('product.show', $product->slug).'"', false);
});

test('product description falls back to the product copy when no meta description is set', function () {
    $category = seoCategory();
    seoProduct($category, ['description' => 'Aromatic long-grain rice for everyday cooking.']);

    $this->get('/product/basmati-rice-5kg')
        ->assertOk()
        ->assertSee('Aromatic long-grain rice for everyday cooking.', false);
});

test('product pages without a meta title never inherit the company title', function () {
    CompanyDetails::firstOrCreate([])->update(['meta_title' => 'Company default title']);
    $category = seoCategory();
    seoProduct($category);

    $html = $this->get('/product/basmati-rice-5kg')->assertOk()->getContent();

    expect($html)->toContain('<title>Basmati Rice 5kg — Alam Mini Market</title>')
        ->and($html)->not->toContain('<title>Company default title</title>');
});

test('tracking and filter parameters never leak into the canonical', function () {
    $category = seoCategory();
    seoProduct($category);

    $this->get('/shop?sort=price_asc&utm_source=newsletter')
        ->assertOk()
        ->assertSee('rel="canonical" href="'.route('shop').'"', false);

    // Text searches are noindexed so result permutations are not indexed.
    $this->get('/shop?q=rice')
        ->assertOk()
        ->assertSee('content="noindex, follow"', false);
});

test('private and transactional pages are noindexed', function () {
    foreach (['/bag', '/login', '/track'] as $uri) {
        $this->get($uri)->assertOk()->assertSee('content="noindex, nofollow"', false);
    }
});

test('robots txt allows crawling and points at the sitemap', function () {
    $response = $this->get('/robots.txt')->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/plain');
    $response->assertSee('User-agent: *', false)
        ->assertSee('Disallow: /admin', false)
        // These pages send noindex themselves, so blocking them here would
        // stop Google from ever reading that directive.
        ->assertDontSee('Disallow: /checkout', false)
        ->assertDontSee('Disallow: /bag', false)
        ->assertDontSee('Disallow: /login', false)
        ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
});

test('sitemap lists active pages, categories and products only', function () {
    $category = seoCategory();
    seoProduct($category);
    $hidden = Product::create([
        'category_id' => $category->id,
        'name' => 'Withdrawn Item',
        'slug' => 'withdrawn-item',
        'status' => false,
    ]);

    $response = $this->get('/sitemap.xml')->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/xml');
    $response->assertSee(route('home'), false)
        ->assertSee(route('shop.category', 'asian-groceries'), false)
        ->assertSee(route('product.show', 'basmati-rice-5kg'), false)
        ->assertDontSee(route('product.show', $hidden->slug), false);
});

test('the sitemap cache is cleared when a product is added', function () {
    $category = seoCategory();
    $this->get('/sitemap.xml')->assertOk();

    $product = seoProduct($category, ['slug' => 'fresh-sitemap-product']);

    $this->get('/sitemap.xml')
        ->assertSee(route('product.show', $product->slug), false);
});

test('hidden products return 404 and renamed slugs redirect permanently', function () {
    $category = seoCategory();
    $gone = Product::create([
        'category_id' => $category->id,
        'name' => 'Gone Product',
        'slug' => 'gone-product',
        'status' => false,
    ]);
    $this->get('/product/'.$gone->slug)->assertStatus(404);

    $product = seoProduct($category);
    $product->update(['slug' => 'basmati-rice-5kg-new']);
    expect(ProductRedirect::where('old_slug', 'basmati-rice-5kg')->exists())->toBeTrue();

    $this->get('/product/basmati-rice-5kg')
        ->assertStatus(301)
        ->assertRedirect(route('product.show', 'basmati-rice-5kg-new'));
});

test('product page emits valid product and breadcrumb structured data', function () {
    $category = seoCategory();
    seoProduct($category);

    $html = $this->get('/product/basmati-rice-5kg')->assertOk()->getContent();

    preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);
    expect($m)->not->toBeEmpty();

    $graph = json_decode(trim($m[1]), true);
    expect($graph)->toBeArray()
        ->and($graph['@context'])->toBe('https://schema.org');

    $types = array_map(fn ($node) => $node['@type'], $graph['@graph']);
    expect($types)->toContain('Product')
        ->and($types)->toContain('BreadcrumbList')
        ->and($types)->toContain('WebSite');

    $product = collect($graph['@graph'])->firstWhere('@type', 'Product');
    expect($product['name'])->toBe('Basmati Rice 5kg')
        ->and($product['sku'])->toBe('RICE-5KG')
        ->and($product['brand']['name'])->toBe('Alam Mini Market')
        ->and($product['offers']['priceCurrency'])->toBe('GBP')
        ->and((float) $product['offers']['price'])->toBe(12.99)
        ->and($product['offers']['availability'])->toBe('https://schema.org/InStock');
});

test('product schema carries the flash price expiry when the price is time-limited', function () {
    $category = seoCategory();
    $product = seoProduct($category);
    $variant = $product->variants()->first();
    $ends = now()->addDay()->startOfMinute();

    FlashSale::create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'promo_price' => 9.99,
        'starts_at' => now()->subHour(),
        'ends_at' => $ends,
        'status' => true,
    ]);

    $html = $this->get('/product/'.$product->slug)->assertOk()->getContent();
    preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);
    $graph = json_decode(trim($m[1]), true);
    $node = collect($graph['@graph'])->firstWhere('@type', 'Product');

    expect((float) $node['offers']['price'])->toBe(9.99)
        ->and($node['offers']['priceValidUntil'])->toBe($ends->toAtomString());
});

test('home page structured data describes the grocery store and website', function () {
    $html = $this->get('/')->assertOk()->getContent();

    preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $m);
    $graph = json_decode(trim($m[1]), true);
    $types = $graph['@graph'];

    $store = collect($types)->first(fn ($node) => in_array('GroceryStore', (array) $node['@type'], true));
    expect($store)->not->toBeNull()
        ->and($store['name'])->toBe('Alam Mini Market')
        ->and($store['url'])->toBe(url('/'));
});

test('page seo rows are surfaced on the matching page and never duplicated', function () {
    PageSeo::create([
        'page_key' => 'delivery',
        'page_label' => 'Delivery Information',
        'meta_title' => 'Northampton Grocery Delivery & Collection',
        'meta_description' => 'Local delivery across Northampton plus free click & collect.',
    ]);

    $html = $this->get('/delivery')->assertOk()->getContent();
    expect(substr_count($html, '<title>'))->toBe(1)
        ->and($html)->toContain('<title>Northampton Grocery Delivery &amp; Collection</title>')
        ->and($html)->not->toContain('Alam Mini Market</title>');
});

test('the complete public page set has one title and the correct robots directive', function () {
    $category = seoCategory();
    seoProduct($category);

    $public = [
        '/', '/about', '/shop', '/shop/asian-groceries', '/product/basmati-rice-5kg',
        '/gallery', '/faq', '/contact', '/privacy-policy', '/terms-of-service', '/refund-policy',
        '/delivery', '/loyalty', '/recipes',
    ];
    foreach ($public as $uri) {
        $html = $this->get($uri)->assertOk()->getContent();
        expect(substr_count($html, '<title>'))->toBe(1, "title count on {$uri}")
            ->and($html)->toContain('content="index, follow"');
    }

    $private = [
        '/bag' => 'noindex, nofollow',
        '/login' => 'noindex, nofollow',
        '/register' => 'noindex, nofollow',
        '/track' => 'noindex, nofollow',
    ];
    foreach ($private as $uri => $robots) {
        $html = $this->get($uri)->assertOk()->getContent();
        expect(substr_count($html, '<title>'))->toBe(1, "title count on {$uri}")
            ->and($html)->toContain('content="'.$robots.'"');
    }
});

test('query-string category URLs 301 to the clean URL and unknown pages 404', function () {
    $category = seoCategory();
    seoProduct($category);

    $this->get('/shop?category=asian-groceries')
        ->assertStatus(301)
        ->assertRedirect(route('shop.category', 'asian-groceries'));

    $this->get('/this-page-does-not-exist')->assertStatus(404);
});

test('customer account pages are noindexed', function () {
    $user = User::create([
        'name' => 'Shopper',
        'email' => 'shopper@example.com',
        'password' => bcrypt('password'),
        'user_type' => 0,
        'status' => 1,
    ]);

    $this->actingAs($user)->get('/account')
        ->assertOk()
        ->assertSee('content="noindex, nofollow"', false);
});

test('admin edits to product and category meta show on the frontend immediately', function () {
    $category = seoCategory(['meta_title' => 'Old category title']);
    $product = seoProduct($category, ['meta_title' => 'Old product title']);

    $this->get('/shop/asian-groceries')->assertSee('<title>Old category title</title>', false);
    $this->get('/product/basmati-rice-5kg')->assertSee('<title>Old product title</title>', false);

    $category->update(['meta_title' => 'New category title', 'meta_description' => 'New category description.']);
    $product->update(['meta_title' => 'New product title', 'meta_description' => 'New product description.']);

    $this->get('/shop/asian-groceries')
        ->assertSee('<title>New category title</title>', false)
        ->assertSee('content="New category description."', false);
    $this->get('/product/basmati-rice-5kg')
        ->assertSee('<title>New product title</title>', false)
        ->assertSee('content="New product description."', false);
});

test('social card titles follow the resolved page title', function () {
    $category = seoCategory();
    seoProduct($category);

    foreach (['/shop', '/delivery', '/product/basmati-rice-5kg'] as $uri) {
        $html = $this->get($uri)->assertOk()->getContent();

        expect($html)->toContain('property="og:title"')
            ->and($html)->toContain('name="twitter:title"');
    }
});

test('the offers page has a clean title distinct from the shop page', function () {
    $category = seoCategory();
    seoProduct($category);

    $offers = $this->get('/shop/offers')->assertOk()->getContent();
    expect(substr_count($offers, '<title>'))->toBe(1)
        ->and($offers)->toContain('<title>Offers | Shop all groceries — Alam Mini Market</title>');

    PageSeo::create([
        'page_key' => 'shop',
        'page_label' => 'Shop',
        'meta_title' => 'Shop All Groceries Online',
        'meta_description' => 'Shop the range.',
    ]);

    $shop = $this->get('/shop')->assertOk()->getContent();
    $offers = $this->get('/shop/offers')->assertOk()->getContent();

    expect($shop)->toContain('<title>Shop All Groceries Online</title>')
        ->and($offers)->toContain('<title>Offers | Shop All Groceries Online</title>');
});

test('categories without copy get a unique generated meta description', function () {
    $category = seoCategory(['name' => 'Rice Grains', 'slug' => 'rice-grains']);
    seoProduct($category);

    $html = $this->get('/shop/rice-grains')->assertOk()->getContent();

    expect($html)->toContain('content="Shop Rice Grains at Alam Mini Market');
});

test('child categories do not inherit their parent meta description', function () {
    $parent = seoCategory([
        'name' => 'World Foods',
        'slug' => 'world-foods',
        'description' => 'Parent description only.',
    ]);
    $child = seoCategory([
        'name' => 'Rice',
        'slug' => 'rice',
        'parent_id' => $parent->id,
    ]);
    seoProduct($child);

    $html = $this->get('/shop/rice')->assertOk()->getContent();

    expect($html)->not->toContain('content="Parent description only."');
});

test('meta text truncation never cuts a word in half', function () {
    expect(Seo::plain('alpha beta gamma delta', 12))->toBe('alpha beta');
});

test('the home page always uses the company meta and ignores any home PageSeo row', function () {
    CompanyDetails::firstOrCreate([])->update([
        'meta_title' => 'Alam Mini Market | Asian Groceries in Northampton',
        'meta_description' => 'Fresh Asian groceries, halal meat and everyday essentials.',
        'meta_keywords' => 'asian groceries, northampton',
    ]);

    PageSeo::create([
        'page_key' => 'home',
        'page_label' => 'Home',
        'meta_title' => 'Stale home title',
        'meta_description' => 'Stale home description.',
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<title>Alam Mini Market | Asian Groceries in Northampton</title>')
        ->and($html)->toContain('content="Fresh Asian groceries, halal meat and everyday essentials."')
        ->and($html)->toContain('content="asian groceries, northampton"')
        ->and($html)->not->toContain('Stale home title');
});

test('the twitter card is emitted once with a valid value', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect(substr_count($html, 'name="twitter:card"'))->toBe(1)
        ->and($html)->toContain('name="twitter:card" content="summary_large_image"');
});

test('company tracking and verification IDs render in the head', function () {
    CompanyDetails::firstOrCreate([])->update([
        'google_site_verification' => 'google-verify-token',
        'google_analytics_id' => 'G-TEST12345',
        'google_tag_manager_id' => 'GTM-TEST123',
        'facebook_pixel_id' => '1234567890',
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<meta name="google-site-verification" content="google-verify-token">')
        ->and($html)->toContain('GTM-TEST123')
        ->and($html)->toContain('G-TEST12345')
        ->and($html)->toContain('1234567890')
        ->and($html)->toContain("getItem('egf-consent')");
});

test('tracking scripts are not emitted when no IDs are configured', function () {
    CompanyDetails::firstOrCreate([])->update([
        'google_site_verification' => null,
        'google_analytics_id' => null,
        'google_tag_manager_id' => null,
        'facebook_pixel_id' => null,
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('googletagmanager.com/gtm.js')
        ->and($html)->not->toContain('googletagmanager.com/gtag/js')
        ->and($html)->not->toContain('connect.facebook.net')
        ->and($html)->not->toContain('google-site-verification');
});

test('the preferred production host redirects www and http variants', function () {
    $this->app['env'] = 'production';
    config([
        'seo.force_canonical_host' => true,
        'app.url' => 'https://alam.example',
    ]);

    $this->get('http://www.alam.example/about')
        ->assertStatus(301)
        ->assertRedirect('https://alam.example/about');

    $this->get('https://www.alam.example/about')
        ->assertStatus(301)
        ->assertRedirect('https://alam.example/about');

    $this->get('https://alam.example/about')->assertOk();
});
