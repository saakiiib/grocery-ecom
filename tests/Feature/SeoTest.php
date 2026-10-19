<?php

use App\Models\Category;
use App\Models\CompanyDetails;
use App\Models\PageSeo;
use App\Models\Product;
use App\Models\ProductRedirect;
use App\Models\ProductVariant;
use App\Models\User;
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
        ->assertSee('Disallow: /checkout', false)
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

test('withdrawn products return 410 and renamed slugs redirect permanently', function () {
    $category = seoCategory();
    $gone = Product::create([
        'category_id' => $category->id,
        'name' => 'Gone Product',
        'slug' => 'gone-product',
        'status' => false,
    ]);
    $this->get('/product/'.$gone->slug)->assertStatus(410);

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
        ->and($product['offers']['priceCurrency'])->toBe('GBP')
        ->and((float) $product['offers']['price'])->toBe(12.99)
        ->and($product['offers']['availability'])->toBe('https://schema.org/InStock');
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
