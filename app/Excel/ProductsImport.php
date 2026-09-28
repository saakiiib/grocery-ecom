<?php

namespace App\Excel;

use App\Models\Category;
use App\Models\OptionGroup;
use App\Models\OptionValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image as ImageFacade;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Excel product import in two phases:
 *  - parse(): read + validate + match, no writes. Returns payload + row errors.
 *  - commit(): apply a parsed payload inside a transaction.
 *
 * Matching keys: SKU → variant; Product ID (then Product Name) → product;
 * Category name → category (auto-create); value label → option value (auto-create).
 * Option GROUP columns must already exist — unknown headers abort the parse.
 */
class ProductsImport
{
    /** Slot filename for a product hero: hero/{slug}.jpg (any image ext accepted). */
    public static function expectedHeroName(string $slug): string
    {
        return 'hero/'.Str::slug($slug).'.jpg';
    }

    /** Slot filename for a variant photo: variants/{SKU}.jpg (any image ext accepted). */
    public static function expectedVariantName(string $sku): string
    {
        return 'variants/'.trim($sku).'.jpg';
    }

    public static function parse(string $path, ?string $imageDir = null): array
    {
        $book = IOFactory::load($path);
        $sheet = $book->getSheetByName('Products') ?? $book->getSheet(0);
        $grid = $sheet->toArray(null, true, true, false);
        if (count($grid) < 2) {
            return ['rows' => [], 'errors' => [['row' => 0, 'message' => 'The Products sheet has no data rows.']], 'stats' => self::emptyStats()];
        }

        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $grid[0]);
        $groups = OptionGroup::with('values')->orderBy('sort_order')->get();
        $groupByHeader = [];
        foreach ($groups as $g) {
            $groupByHeader[mb_strtolower($g->name)] = $g;
        }

        // Unknown headers that are not fixed columns abort the whole parse.
        $fixed = array_map(fn ($h) => mb_strtolower($h), array_merge(ProductsExport::LEAD_COLUMNS, ProductsExport::TAIL_COLUMNS));
        $unknown = [];
        foreach (array_unique($headers) as $h) {
            if ($h === '' || $h === null) {
                continue;
            }
            if (! in_array($h, $fixed, true) && ! isset($groupByHeader[$h])) {
                $unknown[] = $h;
            }
        }
        if ($unknown) {
            return ['rows' => [], 'errors' => [['row' => 1, 'message' => 'Unknown column(s): '.implode(', ', $unknown).'. Option groups must be created by admin first.']], 'stats' => self::emptyStats()];
        }

        $col = fn (string $name) => array_search(mb_strtolower($name), $headers, true);

        $imageMap = self::scanImageDir($imageDir);
        $slugPredictions = [];

        $rows = [];
        $errors = [];
        foreach (array_slice($grid, 1) as $i => $cells) {
            $line = $i + 2;
            if (collect($cells)->filter(fn ($c) => trim((string) $c) !== '')->isEmpty()) {
                continue;
            }
            $get = fn (string $name) => ($c = $col($name)) === false ? null : trim((string) ($cells[$c] ?? ''));
            $norm = self::normalizeRow($line, $get, $groups, $groupByHeader, $headers, $cells, $imageMap, $slugPredictions);
            if (isset($norm['error'])) {
                $errors[] = ['row' => $line, 'message' => $norm['error']];

                continue;
            }
            $rows[] = $norm;
        }

        // Product-level fields must agree across rows of the same product.
        foreach (self::productConflicts($rows) as $message) {
            $errors[] = ['row' => 0, 'message' => $message];
        }

        return ['rows' => $rows, 'errors' => $errors, 'stats' => self::stats($rows), 'images' => self::imageStats($rows, $imageMap)];
    }

    /**
     * Map of lower-cased basenames (and stems) for every image in the dir.
     * Accepts jpg/jpeg/png/webp, any subfolder.
     */
    public static function scanImageDir(?string $dir): array
    {
        $map = [];
        if (! $dir || ! is_dir($dir)) {
            return $map;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $ext = mb_strtolower($file->getExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                continue;
            }
            $base = mb_strtolower($file->getBasename());
            $stem = mb_strtolower(pathinfo($base, PATHINFO_FILENAME));
            $map[$base] = $map[$stem] = $file->getPathname();
        }

        return $map;
    }

    /** Find a slot/expected name in the map, trying each accepted extension. */
    private static function findSlot(array $map, string $expected): ?string
    {
        $stem = mb_strtolower(pathinfo($expected, PATHINFO_FILENAME));
        if (isset($map[$stem])) {
            return $map[$stem];
        }
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $key = $stem.'.'.$ext;
            if (isset($map[$key])) {
                return $map[$key];
            }
        }

        return null;
    }

    /** Predict the slug a new product will get at commit (mirrors commit row order). */
    private static function predictedSlug(string $name, array &$predictions): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $i = 2;
        while (Product::where('slug', $slug)->exists() || in_array($slug, $predictions, true)) {
            $slug = $base.'-'.$i++;
        }
        $predictions[] = $slug;

        return $slug;
    }

    /**
     * Resolve one image cell + slot. Cell wins when filled: URL/path stored
     * as-is, bare filename must exist in the ZIP.
     */
    private static function resolveImage(?string $cell, string $expectedSlot, array $map): array
    {
        $cell = trim((string) $cell);
        if ($cell === '') {
            $found = self::findSlot($map, $expectedSlot);

            return $found ? ['kind' => 'slot', 'file' => $found] : ['kind' => 'none', 'file' => null];
        }
        if (preg_match('~^(https?://|/)~', $cell)) {
            return ['kind' => 'as-is', 'file' => null];
        }
        $base = mb_strtolower(basename($cell));
        $stem = mb_strtolower(pathinfo($base, PATHINFO_FILENAME));
        $found = $map[$base] ?? $map[$stem] ?? null;
        if (! $found) {
            return ['kind' => 'missing', 'file' => null, 'wanted' => basename($cell)];
        }

        return ['kind' => 'cell', 'file' => $found];
    }

    /** @return array normalized row or ['error' => message] */
    private static function normalizeRow(int $line, callable $get, $groups, array $groupByHeader, array $headers, array $cells, array $imageMap, array &$slugPredictions): array
    {
        $name = $get('Product Name');
        if ($name === '' || $name === null) {
            return ['error' => 'Product Name is required.'];
        }

        // Product match: ID wins, else name.
        $productId = $get('Product ID');
        $product = null;
        if ($productId !== '' && $productId !== null) {
            if (! ctype_digit((string) $productId)) {
                return ['error' => "Product ID '{$productId}' is not a number."];
            }
            $product = Product::find($productId);
            if (! $product) {
                return ['error' => "Product ID {$productId} does not exist."];
            }
        } else {
            $matches = Product::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->get();
            if ($matches->count() > 1) {
                return ['error' => "Product Name '{$name}' matches {$matches->count()} products — fill Product ID to disambiguate."];
            }
            $product = $matches->first();
        }

        $categoryName = $get('Category');
        if ($categoryName === '' || $categoryName === null) {
            return ['error' => 'Category is required.'];
        }
        $category = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])->first();

        $mrp = $get('MRP');
        if ($mrp === '' || $mrp === null || ! is_numeric($mrp) || (float) $mrp < 0) {
            return ['error' => "MRP '{$mrp}' must be a number ≥ 0."];
        }
        $offer = $get('Offer Price');
        if ($offer !== '' && $offer !== null) {
            if (! is_numeric($offer) || (float) $offer < 0) {
                return ['error' => "Offer Price '{$offer}' must be a number ≥ 0."];
            }
            if ((float) $offer > (float) $mrp) {
                return ['error' => "Offer Price ({$offer}) cannot exceed MRP ({$mrp})."];
            }
        }

        try {
            $featured = self::bool($get('Featured'), 'Featured');
            $productStatus = self::status($get('Product Status'), 'Product Status');
            $inStock = self::bool($get('In Stock') === '' || $get('In Stock') === null ? 'yes' : $get('In Stock'), 'In Stock');
            $isDefault = self::bool($get('Default'), 'Default');
            $variantStatus = self::status($get('Variant Status') === '' || $get('Variant Status') === null ? 'active' : $get('Variant Status'), 'Variant Status');
        } catch (\InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }

        $sort = $get('Sort Order');
        if ($sort !== '' && $sort !== null && ! ctype_digit((string) $sort)) {
            return ['error' => "Sort Order '{$sort}' must be a whole number."];
        }

        // Option values per group column.
        $groupValues = [];
        foreach ($headers as $idx => $h) {
            if (! isset($groupByHeader[$h])) {
                continue;
            }
            $label = trim((string) ($cells[$idx] ?? ''));
            if ($label === '') {
                continue;
            }
            $group = $groupByHeader[$h];
            $value = $group->values->first(fn ($v) => mb_strtolower($v->label) === mb_strtolower($label));
            $groupValues[$group->id] = $value ? ['id' => $value->id, 'label' => $value->label, 'new' => false]
                : ['id' => null, 'label' => $label, 'new' => true];
        }

        // Extra details: lines of "Label | value".
        $extras = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $get('Extra Details')) as $ln) {
            $ln = trim($ln);
            if ($ln === '') {
                continue;
            }
            if (! str_contains($ln, '|')) {
                return ['error' => "Extra Details line '{$ln}' must look like \"Label | value\"."];
            }
            [$label, $value] = array_map('trim', explode('|', $ln, 2));
            if ($label === '' || $value === '') {
                return ['error' => "Extra Details line '{$ln}' needs both a label and a value."];
            }
            $extras[] = ['label' => $label, 'value' => $value];
        }

        // Variant match by SKU.
        $sku = $get('SKU');
        $sku = ($sku === '' || $sku === null) ? null : $sku;
        $variant = null;
        if ($sku) {
            $variant = ProductVariant::where('sku', $sku)->first();
            if ($variant && $product && (int) $variant->product_id !== (int) $product->id) {
                return ['error' => "SKU '{$sku}' belongs to '{$variant->product->name}', not '{$name}'."];
            }
        } elseif ($product) {
            if ($product->variants()->count() > 1) {
                return ['error' => "Blank SKU with {$product->variants()->count()} existing variants for '{$name}' — fill SKU to choose."];
            }
            $variant = $product->variants()->first();
        }

        // Image slots: hero uses the product slug (predicted for new products).
        $slug = $product?->slug ?? self::predictedSlug($name, $slugPredictions);
        $hero = self::resolveImage($get('Hero Image'), self::expectedHeroName($slug), $imageMap);
        if ($hero['kind'] === 'missing') {
            return ['error' => "Hero Image '{$hero['wanted']}' not found in the uploaded ZIP."];
        }
        $variantImage = null;
        if ($sku) {
            $variantImage = self::resolveImage($get('Variant Image'), self::expectedVariantName($sku), $imageMap);
            if ($variantImage['kind'] === 'missing') {
                return ['error' => "Variant Image '{$variantImage['wanted']}' not found in the uploaded ZIP."];
            }
        }

        return [
            'line' => $line,
            'product_id' => $product?->id,
            'product_name' => $name,
            'category' => ['name' => $categoryName, 'id' => $category?->id, 'new' => ! $category],
            'tagline' => $get('Card Subtitle') ?: null,
            'highlights' => $get('Key Points') ?: null,
            'description' => $get('Description') ?: null,
            'extras' => $extras,
            'hero_image' => $get('Hero Image') ?: null,
            'hero_source' => $hero,
            'variant_source' => $variantImage,
            'is_featured' => $featured,
            'product_status' => $productStatus,
            'sort_order' => $sort === '' || $sort === null ? null : (int) $sort,
            'meta_title' => $get('Meta Title') ?: null,
            'meta_keywords' => $get('Meta Keywords') ?: null,
            'meta_description' => $get('Meta Description') ?: null,
            'group_values' => $groupValues,
            'variant_id' => $variant?->id,
            'sku' => $sku,
            'mrp' => (float) $mrp,
            'offer_price' => ($offer === '' || $offer === null) ? null : (float) $offer,
            'in_stock' => $inStock,
            'is_default' => $isDefault,
            'variant_image' => $get('Variant Image') ?: null,
            'variant_status' => $variantStatus,
        ];
    }

    private static function bool(?string $v, string $field): bool
    {
        $v = mb_strtolower(trim((string) $v));
        if (in_array($v, ['yes', 'y', '1', 'true', 'active'], true)) {
            return true;
        }
        if (in_array($v, ['no', 'n', '0', 'false', '', 'disabled'], true)) {
            return false;
        }
        throw new \InvalidArgumentException("{$field} '{$v}' must be yes/no.");
    }

    private static function status(?string $v, string $field): bool
    {
        $v = mb_strtolower(trim((string) $v));
        if (in_array($v, ['active', 'yes', 'y', '1', 'true'], true)) {
            return true;
        }
        if (in_array($v, ['disabled', 'no', 'n', '0', 'false', ''], true)) {
            return false;
        }
        throw new \InvalidArgumentException("{$field} '{$v}' must be active/disabled.");
    }

    /** Group rows by product key and flag disagreeing product-level fields. */
    private static function productConflicts(array $rows): array
    {
        $byKey = [];
        foreach ($rows as $r) {
            $key = $r['product_id'] ?? 'n:'.mb_strtolower($r['product_name']);
            $byKey[$key][] = $r;
        }
        $conflicts = [];
        $fields = ['product_name', 'category', 'tagline', 'hero_image'];
        foreach ($byKey as $key => $group) {
            if (count($group) < 2) {
                continue;
            }
            $first = $group[0];
            foreach ($fields as $f) {
                $vals = array_unique(array_map(fn ($r) => $f === 'category' ? mb_strtolower($r['category']['name']) : mb_strtolower((string) ($r[$f] ?? '')), $group));
                if (count($vals) > 1) {
                    $conflicts[] = "Rows for '{$first['product_name']}' disagree on {$f} — keep product columns identical across its rows.";
                    break;
                }
            }
            // Same SKU twice in the sheet.
            $skus = array_filter(array_map(fn ($r) => $r['sku'], $group));
            if (count($skus) !== count(array_unique($skus))) {
                $conflicts[] = "Rows for '{$first['product_name']}' repeat a SKU — each variant row needs its own SKU.";
            }
        }

        return $conflicts;
    }

    private static function emptyStats(): array
    {
        return ['products_new' => 0, 'products_updated' => 0, 'variants_new' => 0, 'variants_updated' => 0, 'categories_new' => [], 'values_new' => []];
    }

    /** Matched vs unused ZIP files for the preview. */
    private static function imageStats(array $rows, array $map): array
    {
        $used = [];
        $hero = 0;
        $variants = 0;
        foreach ($rows as $r) {
            foreach (['hero_source', 'variant_source'] as $key) {
                $src = $r[$key] ?? null;
                if (! $src || ! in_array($src['kind'], ['slot', 'cell'], true)) {
                    continue;
                }
                $used[$src['file']] = true;
                if ($key === 'hero_source') {
                    $hero++;
                } else {
                    $variants++;
                }
            }
        }
        $unused = [];
        foreach (array_unique(array_values($map)) as $path) {
            if (! isset($used[$path])) {
                $unused[] = basename($path);
            }
        }
        sort($unused);

        return ['zip_files' => count(array_unique(array_values($map))), 'hero_matched' => $hero, 'variant_matched' => $variants, 'unused' => $unused];
    }

    private static function stats(array $rows): array
    {
        $stats = self::emptyStats();
        $seenProducts = [];
        foreach ($rows as $r) {
            $key = $r['product_id'] ?? 'n:'.mb_strtolower($r['product_name']);
            if (! isset($seenProducts[$key])) {
                $seenProducts[$key] = true;
                if ($r['product_id']) {
                    $stats['products_updated']++;
                } else {
                    $stats['products_new']++;
                }
            }
            if ($r['variant_id']) {
                $stats['variants_updated']++;
            } else {
                $stats['variants_new']++;
            }
            if ($r['category']['new']) {
                $stats['categories_new'][$r['category']['name']] = true;
            }
            foreach ($r['group_values'] as $gid => $gv) {
                if ($gv['new']) {
                    $stats['values_new'][] = $gv['label'];
                }
            }
        }
        $stats['categories_new'] = array_keys($stats['categories_new']);
        $stats['values_new'] = array_values(array_unique($stats['values_new']));

        return $stats;
    }

    /**
     * Apply a parsed payload. Returns summary counts.
     * $disableMissing: variants of touched products absent from the sheet → disabled.
     * $imageDir: extracted ZIP dir; slot/cell files are converted and saved.
     */
    public static function commit(array $rows, bool $disableMissing = false, ?string $imageDir = null): array
    {
        return DB::transaction(function () use ($rows, $disableMissing) {
            $categoryCache = [];
            $valueCache = [];
            $touchedProducts = [];
            $seenVariantIds = [];
            $heroJobs = [];
            $variantJobs = [];
            $summary = ['products_created' => 0, 'products_updated' => 0, 'variants_created' => 0, 'variants_updated' => 0, 'images_saved' => 0, 'image_warnings' => []];

            // First pass: categories + values + products + variants.
            foreach ($rows as $r) {
                if (! isset($categoryCache[$r['category']['name']])) {
                    $categoryCache[$r['category']['name']] = $r['category']['id']
                        ?? Category::create([
                            'name' => $r['category']['name'],
                            'slug' => self::uniqueSlug($r['category']['name'], Category::class),
                            'status' => true,
                            'sort_order' => (int) (Category::max('sort_order') ?? 0) + 1,
                        ])->id;
                }
                $categoryId = $categoryCache[$r['category']['name']];

                $valueIds = [];
                foreach ($r['group_values'] as $gid => $gv) {
                    if ($gv['id']) {
                        $valueIds[] = $gv['id'];

                        continue;
                    }
                    $ckey = $gid.':'.mb_strtolower($gv['label']);
                    if (! isset($valueCache[$ckey])) {
                        $group = OptionGroup::findOrFail($gid);
                        $valueCache[$ckey] = OptionValue::create([
                            'option_group_id' => $gid,
                            'label' => $gv['label'],
                            'slug' => self::uniqueValueSlug($gid, $gv['label']),
                            'status' => true,
                            'sort_order' => (int) (OptionValue::where('option_group_id', $gid)->max('sort_order') ?? 0) + 1,
                        ])->id;
                    }
                    $valueIds[] = $valueCache[$ckey];
                }

                // Product: first row of each product writes product-level fields.
                $pkey = $r['product_id'] ?? 'n:'.mb_strtolower($r['product_name']);
                if (! isset($touchedProducts[$pkey])) {
                    if ($r['product_id']) {
                        $product = Product::findOrFail($r['product_id']);
                        $product->update([
                            'category_id' => $categoryId,
                            'name' => $r['product_name'],
                            'tagline' => $r['tagline'],
                            'highlights' => $r['highlights'],
                            'description' => $r['description'],
                            'hero_image' => $r['hero_image'],
                            'is_featured' => $r['is_featured'],
                            'status' => $r['product_status'],
                            'meta_title' => $r['meta_title'],
                            'meta_keywords' => $r['meta_keywords'],
                            'meta_description' => $r['meta_description'],
                            ...(is_null($r['sort_order']) ? [] : ['sort_order' => $r['sort_order']]),
                        ]);
                        $summary['products_updated']++;
                    } else {
                        $product = Product::create([
                            'category_id' => $categoryId,
                            'name' => $r['product_name'],
                            'slug' => self::uniqueSlug($r['product_name'], Product::class),
                            'tagline' => $r['tagline'],
                            'highlights' => $r['highlights'],
                            'description' => $r['description'],
                            'hero_image' => $r['hero_image'],
                            'is_featured' => $r['is_featured'],
                            'status' => $r['product_status'],
                            'sort_order' => $r['sort_order'] ?? (int) (Product::max('sort_order') ?? 0) + 1,
                            'meta_title' => $r['meta_title'],
                            'meta_keywords' => $r['meta_keywords'],
                            'meta_description' => $r['meta_description'],
                        ]);
                        $summary['products_created']++;
                    }
                    // Extra details: replace-all from the first row.
                    $product->extraAttributes()->delete();
                    foreach (array_values($r['extras']) as $i => $ex) {
                        $product->extraAttributes()->create([...$ex, 'sort_order' => $i]);
                    }
                    $touchedProducts[$pkey] = $product->id;
                }
                $productId = $touchedProducts[$pkey];

                if ($r['variant_id']) {
                    $variant = ProductVariant::findOrFail($r['variant_id']);
                    $variant->update([
                        'sku' => $r['sku'],
                        'mrp' => $r['mrp'],
                        'offer_price' => $r['offer_price'],
                        'in_stock' => $r['in_stock'],
                        'is_default' => $r['is_default'],
                        'image' => $r['variant_image'],
                        'status' => $r['variant_status'],
                    ]);
                    $summary['variants_updated']++;
                } else {
                    $variant = ProductVariant::create([
                        'product_id' => $productId,
                        'sku' => $r['sku'],
                        'mrp' => $r['mrp'],
                        'offer_price' => $r['offer_price'],
                        'in_stock' => $r['in_stock'],
                        'is_default' => $r['is_default'],
                        'image' => $r['variant_image'],
                        'status' => $r['variant_status'],
                        'sort_order' => (int) (ProductVariant::where('product_id', $productId)->max('sort_order') ?? -1) + 1,
                    ]);
                    $summary['variants_created']++;
                }
                $variant->values()->sync($valueIds);
                $seenVariantIds[$productId][] = $variant->id;

                // Image jobs: hero once per product (first row), variant photo per row.
                if (! isset($heroJobs[$productId]) && in_array($r['hero_source']['kind'] ?? 'none', ['slot', 'cell'], true)) {
                    $heroJobs[$productId] = $r['hero_source']['file'];
                }
                if (in_array($r['variant_source']['kind'] ?? 'none', ['slot', 'cell'], true)) {
                    $variantJobs[$variant->id] = $r['variant_source']['file'];
                }
            }

            // Convert + save staged images (same webp pipeline as the admin UI).
            foreach ($heroJobs as $pid => $src) {
                $product = Product::find($pid);
                $saved = $product ? self::storeImportImage($src, 'uploads/products/', 1600) : null;
                if ($saved) {
                    self::deletePublicFile($product->hero_image);
                    $product->update(['hero_image' => $saved]);
                    $summary['images_saved']++;
                } else {
                    $summary['image_warnings'][] = "Hero photo for '{$product->name}' could not be converted.";
                }
            }
            foreach ($variantJobs as $vid => $src) {
                $variant = ProductVariant::find($vid);
                $saved = $variant ? self::storeImportImage($src, 'uploads/products/variants/', 800) : null;
                if ($saved) {
                    self::deletePublicFile($variant->image);
                    $variant->update(['image' => $saved]);
                    $summary['images_saved']++;
                } else {
                    $summary['image_warnings'][] = "Photo for variant '{$variant->sku}' could not be converted.";
                }
            }

            // Second pass per touched product: exactly one default + override sync + missing.
            foreach (array_unique(array_values($touchedProducts)) as $pid) {
                $ordered = ProductVariant::where('product_id', $pid)->orderBy('sort_order')->get();
                $marked = $ordered->where('is_default', true)->values();
                if ($marked->isNotEmpty()) {
                    $keep = $marked->first()->id;
                    ProductVariant::where('product_id', $pid)->where('id', '!=', $keep)->update(['is_default' => false]);
                } elseif ($ordered->isNotEmpty()) {
                    $ordered->first()->update(['is_default' => true]);
                }

                if ($disableMissing && isset($seenVariantIds[$pid])) {
                    ProductVariant::where('product_id', $pid)
                        ->whereNotIn('id', $seenVariantIds[$pid])
                        ->update(['status' => false]);
                }

                self::syncProductOverrideGroups($pid);
            }

            return $summary;
        });
    }

    /** Convert a staged upload to webp in the public folder. Null on failure. */
    private static function storeImportImage(string $src, string $dir, int $width): ?string
    {
        try {
            $path = public_path($dir);
            if (! file_exists($path)) {
                mkdir($path, 0755, true);
            }
            $name = mt_rand(10000000, 99999999).'.webp';
            ImageFacade::make($src)->resize($width, null, function ($c) {
                $c->aspectRatio();
                $c->upsize();
            })->encode('webp', 75)->save($path.$name);

            return '/'.$dir.$name;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function deletePublicFile(?string $path): void
    {
        if ($path && $path !== 'placeholder.webp' && file_exists(public_path($path))) {
            @unlink(public_path($path));
        }
    }

    /**
     * Keep template/override truthful: product override = groups its variants
     * actually use, unless identical to the category template (then inherit).
     */
    private static function syncProductOverrideGroups(int $productId): void
    {
        $product = Product::with(['category', 'variants.values'])->findOrFail($productId);
        $used = $product->variants->flatMap(fn ($v) => $v->values->pluck('option_group_id'))->unique()->sort()->values();
        $template = $product->category
            ? $product->category->optionGroups()->pluck('option_groups.id')->sort()->values()
            : collect();
        if ($used->all() === $template->all()) {
            $product->optionGroups()->detach();

            return;
        }
        $sync = [];
        foreach ($used->values()->all() as $i => $gid) {
            $sync[$gid] = ['sort_order' => $i];
        }
        $product->optionGroups()->sync($sync);
    }

    private static function uniqueSlug(string $name, string $model): string
    {
        $base = Str::slug($name) ?: 'item';
        $slug = $base;
        $i = 2;
        while ($model::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private static function uniqueValueSlug(int $groupId, string $label): string
    {
        $base = Str::slug($label) ?: 'value';
        $slug = $base;
        $i = 2;
        while (OptionValue::where('option_group_id', $groupId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
