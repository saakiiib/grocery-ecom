<?php

use App\Excel\ProductsExport;
use App\Excel\ProductsImport;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function dietFixtures(): array
{
    Allergen::seedDefaults();
    $cat = Category::create(['name' => 'Dairy', 'slug' => 'dairy']);
    $milk = Product::create(['category_id' => $cat->id, 'name' => 'Whole Milk', 'slug' => 'whole-milk', 'status' => true, 'is_vegetarian' => true, 'origin_country' => 'United Kingdom', 'nutrition_per' => 'per 100ml', 'energy_kcal' => 64, 'fat_g' => 3.6]);
    ProductVariant::create(['product_id' => $milk->id, 'sku' => 'MILK-1L', 'mrp' => 2.00, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);
    $milk->allergens()->sync(Allergen::where('slug', 'milk')->pluck('id')->all());

    $oats = Product::create(['category_id' => $cat->id, 'name' => 'Oat Drink', 'slug' => 'oat-drink', 'status' => true, 'is_vegetarian' => true, 'is_vegan' => true]);
    ProductVariant::create(['product_id' => $oats->id, 'sku' => 'OAT-1L', 'mrp' => 2.50, 'is_default' => true, 'in_stock' => true, 'status' => true, 'sort_order' => 0]);

    $admin = User::create(['name' => 'Admin', 'email' => 'diet-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    return compact('milk', 'oats', 'admin');
}

test('admin can save diet, origin, nutrition and allergens', function () {
    ['milk' => $milk, 'admin' => $admin] = dietFixtures();
    $nuts = Allergen::where('slug', 'nuts')->firstOrFail();

    $this->actingAs($admin)->postJson(route('products.update'), [
        'codeid' => $milk->id, 'name' => 'Whole Milk', 'category_id' => $milk->category_id,
        'origin_country' => 'Jersey', 'nutrition_per' => 'per 100ml',
        'energy_kcal' => 65, 'fat_g' => 3.7,
        'is_vegan' => '1',
        'allergens' => [$nuts->id],
    ])->assertOk();

    $milk->refresh();
    expect($milk->origin_country)->toBe('Jersey')
        ->and($milk->is_vegan)->toBeTrue()
        ->and((float) $milk->energy_kcal)->toBe(65.0)
        ->and($milk->allergens->pluck('slug')->all())->toBe(['nuts']);
});

test('shop filters by diet and free-from', function () {
    dietFixtures();

    $html = $this->get(route('shop', ['diet' => ['vegan']]))->assertOk()->getContent();
    expect($html)->toContain('/product/oat-drink')->not->toContain('/product/whole-milk');

    $html = $this->get(route('shop', ['diet' => ['vegetarian']]))->assertOk()->getContent();
    expect($html)->toContain('/product/whole-milk')->toContain('/product/oat-drink');

    $html = $this->get(route('shop', ['free_from' => ['milk']]))->assertOk()->getContent();
    expect($html)->not->toContain('/product/whole-milk')->toContain('/product/oat-drink');

    $html = $this->get(route('shop', ['diet' => ['vegan'], 'free_from' => ['milk']]))->assertOk()->getContent();
    expect($html)->toContain('/product/oat-drink');
});

test('details page shows diet, origin, allergens and nutrition', function () {
    ['milk' => $milk] = dietFixtures();

    $this->get(route('product.show', $milk->slug))->assertOk()
        ->assertSee('Vegetarian')
        ->assertSee('United Kingdom')
        ->assertSee('Allergy advice')
        ->assertSee('Milk')
        ->assertSee('Nutrition')
        ->assertSee('64 kcal');
});

test('excel roundtrips diet columns and rejects unknown allergens', function () {
    dietFixtures();

    $book = ProductsExport::build();
    $grid = $book->getSheetByName('Products')->toArray(null, true, true, false);
    $headers = $grid[0];
    $col = fn ($n) => array_search($n, $headers, true);
    expect($col('Vegan'))->not->toBeFalse()
        ->and($col('Allergens'))->not->toBeFalse()
        ->and($col('Energy (kcal)'))->not->toBeFalse();

    $rows = array_slice($grid, 1);
    foreach ($rows as &$r) {
        if ($r[$col('SKU')] === 'OAT-1L') {
            $r[$col('Vegan')] = 'yes';
            $r[$col('Halal')] = 'yes';
            $r[$col('Allergens')] = 'Nuts';
            $r[$col('Energy (kcal)')] = 45;
        }
        if ($r[$col('SKU')] === 'MILK-1L') {
            $r[$col('Allergens')] = 'Unicorn';
        }
    }
    unset($r);

    $path = storage_path('app/test-diet.xlsx');
    $write = function () use ($headers, &$rows, $path) {
        $edited = new Spreadsheet;
        $edited->getActiveSheet()->setTitle('Products');
        $edited->getActiveSheet()->fromArray([$headers, ...$rows], null, 'A1');
        (new Xlsx($edited))->save($path);
    };
    $write();

    $result = ProductsImport::parse($path);
    expect(count($result['errors']))->toBe(1)
        ->and($result['errors'][0]['message'])->toContain('Unicorn');

    // Fix the bad row and commit: OAT-1L gains halal + nuts + 45 kcal.
    foreach ($rows as &$r) {
        if ($r[$col('SKU')] === 'MILK-1L') {
            $r[$col('Allergens')] = 'Milk';
        }
    }
    unset($r);
    $write();
    $result = ProductsImport::parse($path);
    expect($result['errors'])->toBe([]);

    ProductsImport::commit($result['rows']);
    $oats = Product::where('slug', 'oat-drink')->firstOrFail();
    expect($oats->is_halal)->toBeTrue()
        ->and((float) $oats->energy_kcal)->toBe(45.0)
        ->and($oats->allergens->pluck('slug')->all())->toBe(['nuts']);
});
