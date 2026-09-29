<?php

use App\Excel\ProductsExport;
use App\Excel\ProductsImport;
use App\Models\Category;
use App\Models\OptionGroup;
use App\Models\OptionValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

function groceryFixtures(): array
{
    $cat = Category::create(['name' => 'Fresh Meat', 'slug' => 'fresh-meat']);
    $group = OptionGroup::create(['name' => 'Pack Size', 'slug' => 'pack-size', 'type' => 'buttons', 'sort_order' => 0]);
    $v500 = OptionValue::create(['option_group_id' => $group->id, 'label' => '500g', 'slug' => '500g', 'sort_order' => 0]);
    $v1kg = OptionValue::create(['option_group_id' => $group->id, 'label' => '1kg', 'slug' => '1kg', 'sort_order' => 1]);
    $cat->optionGroups()->sync([$group->id => ['sort_order' => 0]]);

    $product = Product::create(['category_id' => $cat->id, 'name' => 'Lamb Leg', 'slug' => 'lamb-leg', 'highlights' => "Grass fed\nFresh"]);
    $a = ProductVariant::create(['product_id' => $product->id, 'sku' => 'LAMB-500', 'mrp' => 12.99, 'offer_price' => 10.99, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);
    $a->values()->sync([$v500->id]);
    $b = ProductVariant::create(['product_id' => $product->id, 'sku' => 'LAMB-1KG', 'mrp' => 22.99, 'is_default' => false, 'in_stock' => true, 'status' => true, 'sort_order' => 1]);
    $b->values()->sync([$v1kg->id]);

    return compact('cat', 'group', 'v500', 'v1kg', 'product', 'a', 'b');
}

test('product store auto-creates a default variant at mrp zero', function () {
    $admin = adminUser();
    $cat = Category::create(['name' => 'Pantry', 'slug' => 'pantry']);

    $response = $this->actingAs($admin)->post(route('products.store'), [
        'name' => 'Sea Salt', 'category_id' => $cat->id,
    ]);

    $response->assertOk()->assertJson(['message' => 'Product created successfully. Set its prices in the Variants tab.']);

    $product = Product::where('slug', 'sea-salt')->firstOrFail();
    expect($product->variants)->toHaveCount(1);
    $default = $product->defaultVariant();
    expect($default->is_default)->toBeTrue()
        ->and((float) $default->mrp)->toBe(0.0)
        ->and($default->in_stock)->toBeTrue();
});

test('variant store enforces price, sku and one-value-per-group rules', function () {
    $admin = adminUser();
    ['product' => $product, 'v500' => $v500, 'v1kg' => $v1kg] = groceryFixtures();

    // Offer above MRP is rejected.
    $this->actingAs($admin)->post(route('product-variants.store', $product->id), [
        'sku' => 'BAD-1', 'mrp' => 5, 'offer_price' => 9,
    ])->assertStatus(302)->assertInvalid('offer_price');

    // Two values from the same group are rejected with 422.
    $this->actingAs($admin)->post(route('product-variants.store', $product->id), [
        'sku' => 'BAD-2', 'mrp' => 5, 'value_ids' => [$v500->id, $v1kg->id],
    ])->assertStatus(422);

    // Duplicate SKU is rejected.
    $this->actingAs($admin)->post(route('product-variants.store', $product->id), [
        'sku' => 'LAMB-500', 'mrp' => 5,
    ])->assertStatus(302)->assertInvalid('sku');

    // A valid row saves with its combination.
    $response = $this->actingAs($admin)->post(route('product-variants.store', $product->id), [
        'sku' => 'LAMB-2KG', 'mrp' => 40, 'value_ids' => [$v1kg->id],
    ]);
    $response->assertOk()->assertJson(['message' => 'Variant added']);
    expect(ProductVariant::where('sku', 'LAMB-2KG')->firstOrFail()->values)->toHaveCount(1);
});

test('default variant lifecycle never leaves a product without a default', function () {
    $admin = adminUser();
    ['product' => $product, 'a' => $a, 'b' => $b] = groceryFixtures();

    $this->actingAs($admin)->post(route('product-variants.default', $b->id))->assertOk();
    expect($a->fresh()->is_default)->toBeFalse()
        ->and($b->fresh()->is_default)->toBeTrue();

    $this->actingAs($admin)->delete(route('product-variants.delete', $b->id))->assertOk();
    expect($product->variants()->where('is_default', true)->count())->toBe(1)
        ->and($a->fresh()->is_default)->toBeTrue();
});

test('price helpers and product text helpers behave', function () {
    ['product' => $product, 'a' => $a, 'b' => $b] = groceryFixtures();

    expect($a->sellingPrice())->toBe(10.99)
        ->and($b->sellingPrice())->toBe(22.99)
        ->and($product->priceRange())->toContain('10.99')
        ->and($product->priceRange())->toContain('22.99')
        ->and($product->highlightList())->toBe(['Grass fed', 'Fresh']);

    $product->extraAttributes()->create(['label' => 'Storage', 'value' => 'Keep chilled', 'sort_order' => 0]);
    expect($product->extraAttributes()->first()->label)->toBe('Storage');
});

test('option group admin guards names, types and in-use deletes', function () {
    $admin = adminUser();
    ['group' => $group, 'v500' => $v500] = groceryFixtures();

    $this->actingAs($admin)->post(route('option-groups.store'), [
        'name' => 'Pack Size', 'type' => 'buttons',
    ])->assertStatus(302)->assertInvalid('name');

    $this->actingAs($admin)->post(route('option-groups.store'), [
        'name' => 'Spice', 'type' => 'radio',
    ])->assertStatus(302)->assertInvalid('type');

    // Group attached to a category template cannot be deleted.
    $this->actingAs($admin)->delete(route('option-groups.delete', $group->id))
        ->assertStatus(422)->assertJsonFragment(['message' => 'This group is used in a category template or product override — remove it there first']);

    // Value used by a variant cannot be deleted.
    $this->actingAs($admin)->delete(route('option-values.delete', $v500->id))
        ->assertStatus(422);

    // Unused group with unused values deletes cleanly.
    $fresh = $this->actingAs($admin)->post(route('option-groups.store'), ['name' => 'Spice', 'type' => 'dropdown']);
    $fresh->assertOk();
    $gid = $fresh->getData()->group->id;
    $this->actingAs($admin)->post(route('option-groups.values.store', $gid), ['label' => 'Hot'])->assertOk();
    $this->actingAs($admin)->delete(route('option-groups.delete', $gid))->assertOk();
    expect(OptionValue::where('option_group_id', $gid)->count())->toBe(0);
});

test('category template syncs and product override replaces it', function () {
    $admin = adminUser();
    ['cat' => $cat, 'group' => $group, 'product' => $product] = groceryFixtures();

    $other = OptionGroup::create(['name' => 'Cut', 'slug' => 'cut', 'type' => 'buttons', 'sort_order' => 1]);

    // Template change via category update.
    $this->actingAs($admin)->post(route('category.update'), [
        'codeid' => $cat->id, 'name' => 'Fresh Meat', 'option_group_ids' => [$other->id],
    ])->assertOk();
    expect($cat->fresh()->optionGroups()->pluck('option_groups.id')->all())->toBe([$other->id]);

    // Product inherits the template until it overrides.
    expect($product->effectiveOptionGroups()->pluck('id')->all())->toBe([$other->id]);

    $this->actingAs($admin)->post(route('product-variants.syncGroups', $product->id), [
        'group_ids' => [$group->id],
    ])->assertOk();
    expect($product->refresh()->effectiveOptionGroups()->pluck('id')->all())->toBe([$group->id]);

    // Empty override clears back to inheritance.
    $this->actingAs($admin)->post(route('product-variants.syncGroups', $product->id), ['group_ids' => []])->assertOk();
    expect($product->refresh()->effectiveOptionGroups()->pluck('id')->all())->toBe([$other->id]);
});

test('excel export, import and image slots roundtrip', function () {
    groceryFixtures();

    $book = ProductsExport::build();
    expect($book->getSheetNames())->toBe(['Products', 'Reference', 'Image Slots', 'Guide']);

    $grid = $book->getSheetByName('Products')->toArray(null, true, true, false);
    $headers = $grid[0];
    $col = fn ($n) => array_search($n, $headers, true);
    expect($col('SKU'))->not->toBeFalse()
        ->and($col('Pack Size'))->not->toBeFalse()
        ->and(count($grid) - 1)->toBe(2);

    // Edit: price change, new value, new product + category, one bad row.
    $rows = array_slice($grid, 1);
    foreach ($rows as &$r) {
        if ($r[$col('SKU')] === 'LAMB-500') {
            $r[$col('Offer Price')] = 9.49;
        }
    }
    unset($r);
    $new = $rows[0];
    $new[$col('SKU')] = 'LAMB-DICED';
    $new[$col('Pack Size')] = 'Diced';
    $new[$col('MRP')] = 15;
    $new[$col('Offer Price')] = null;
    $rows[] = $new;
    $blank = array_fill(0, count($headers), null);
    $blank[$col('SKU')] = 'YOG-1';
    $blank[$col('Product Name')] = 'Yogurt';
    $blank[$col('Category')] = 'Dairy';
    $blank[$col('MRP')] = 3;
    $blank[$col('In Stock')] = 'yes';
    $blank[$col('Variant Status')] = 'active';
    $blank[$col('Product Status')] = 'active';
    $rows[] = $blank;
    $bad = $blank;
    $bad[$col('SKU')] = 'BAD-9';
    $bad[$col('Product Name')] = 'Bad';
    $bad[$col('Offer Price')] = 9;
    $rows[] = $bad;

    $path = storage_path('app/test-admin-catalog.xlsx');
    $edited = new Spreadsheet;
    $edited->getActiveSheet()->setTitle('Products');
    $edited->getActiveSheet()->fromArray([$headers, ...$rows], null, 'A1');
    (new Xlsx($edited))->save($path);

    // Images: one hero shared by lamb rows, one variant photo.
    $imgDir = storage_path('app/test-admin-catalog-img');
    @mkdir("{$imgDir}/hero", 0755, true);
    @mkdir("{$imgDir}/variants", 0755, true);
    Image::canvas(200, 200, '#a3c585')->save("{$imgDir}/hero/lamb-leg.png");
    Image::canvas(200, 200, '#c58585')->save("{$imgDir}/variants/LAMB-500.JPG");

    $result = ProductsImport::parse($path, $imgDir);
    expect(count($result['rows']))->toBe(4)
        ->and(count($result['errors']))->toBe(1)
        ->and($result['stats']['categories_new'])->toBe(['Dairy'])
        ->and($result['images']['hero_matched'])->toBe(3)
        ->and($result['images']['variant_matched'])->toBe(1);

    $summary = ProductsImport::commit($result['rows'], false, $imgDir);
    expect($summary['products_created'])->toBe(1)
        ->and($summary['variants_created'])->toBe(2)
        ->and($summary['images_saved'])->toBe(2)
        ->and($summary['image_warnings'])->toBe([]);

    expect((float) ProductVariant::where('sku', 'LAMB-500')->firstOrFail()->offer_price)->toBe(9.49)
        ->and(Category::where('name', 'Dairy')->exists())->toBeTrue()
        ->and(OptionValue::where('label', 'Diced')->count())->toBe(1);

    $hero = Product::where('slug', 'lamb-leg')->firstOrFail()->hero_image;
    expect($hero)->not->toBeNull()
        ->and(file_exists(public_path($hero)))->toBeTrue();
    @unlink(public_path($hero));
    foreach (ProductVariant::whereNotNull('image')->get() as $v) {
        @unlink(public_path($v->image));
    }

    @unlink($path);
    array_map('unlink', glob("{$imgDir}/hero/*") ?: []);
    array_map('unlink', glob("{$imgDir}/variants/*") ?: []);
    @rmdir("{$imgDir}/hero");
    @rmdir("{$imgDir}/variants");
    @rmdir($imgDir);
});

test('admin catalog pages render for admins and redirect guests', function () {    // Guests first: actingAs persists for the rest of the test.
    $this->get(route('products.index'))->assertRedirect(route('login'));
    $this->get(route('option-groups.index'))->assertRedirect(route('login'));

    $admin = adminUser();
    ['product' => $product, 'group' => $group] = groceryFixtures();

    foreach ([route('products.index'), route('option-groups.index'), route('products.manage', $product->id), route('option-groups.manage', $group->id)] as $url) {
        $this->actingAs($admin)->get($url)->assertOk($url);
    }
});

test('sliders store full slide content, sort and toggle', function () {
    $admin = adminUser();

    $this->actingAs($admin)->get(route('slider.index'))->assertOk();

    $response = $this->actingAs($admin)->post(route('slider.store'), [
        'badge' => 'Fresh picks',
        'title' => 'Good food.',
        'subtitle' => 'Bring home the good stuff.',
        'btn_text' => 'Shop groceries',
        'btn_url' => '/collections',
        'btn_text2' => 'Offers',
        'btn_url2' => '/about',
        'image' => UploadedFile::fake()->image('hero.jpg', 1920, 800),
    ]);
    $response->assertOk()->assertJson(['success' => true]);

    $slide = Slider::where('title', 'Good food.')->firstOrFail();
    expect($slide->badge)->toBe('Fresh picks')
        ->and($slide->btn_text2)->toBe('Offers')
        ->and($slide->image)->toEndWith('.webp')
        ->and(file_exists(public_path($slide->image)))->toBeTrue();

    // Image is required on create.
    $this->actingAs($admin)->post(route('slider.store'), ['title' => 'No image'])
        ->assertStatus(302)->assertInvalid('image');

    $second = Slider::create(['title' => 'Second', 'sort_order' => 99, 'is_active' => true]);
    $this->actingAs($admin)->post(route('slider.sortUpdate'), ['ids' => [$second->id, $slide->id]])->assertOk();
    expect($second->fresh()->sort_order)->toBe(0)
        ->and($slide->fresh()->sort_order)->toBe(1);

    $this->actingAs($admin)->post(route('slider.toggleStatus'), ['id' => $slide->id])->assertOk();
    expect($slide->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->delete(route('slider.delete', $slide->id))->assertOk();
    expect(file_exists(public_path($slide->image)))->toBeFalse();
});
