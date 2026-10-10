<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * Bulk Photos: drop product photos without Excel, ZIPs or exact filenames.
 *
 * Step 1 (index): dropzone, up to MAX_FILES photos per batch.
 * Step 2 (upload): files staged in storage/app/bulk-photos/{token}/ and
 *   auto-matched by filename against variant SKUs / product slugs.
 * Step 3 (confirm): matched + manually assigned photos converted to webp
 *   through the same pipeline as the Manage page (hero 1600px,
 *   variant 800px), old files deleted.
 */
class BulkPhotoController extends Controller
{
    private const MAX_FILES = 20;

    /** Dropzone page with catalog counts. */
    public function index()
    {
        $stats = [
            'products' => Product::count(),
            'variants' => ProductVariant::count(),
            'with_hero' => Product::whereNotNull('hero_image')->count(),
            'variants_with_photo' => ProductVariant::whereNotNull('image')->count(),
        ];

        return view('admin.products.bulk-photos', compact('stats'));
    }

    /** Store the dropped files, auto-match, show the review grid. */
    public function upload(Request $request)
    {
        $request->validate([
            'photos' => 'required|array|max:'.self::MAX_FILES,
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $token = Str::random(32);
        $dir = 'bulk-photos/'.$token;
        $files = [];
        foreach ($request->file('photos') as $file) {
            $name = $this->uniqueName($dir, $file->getClientOriginalName());
            $file->storeAs($dir, $name);
            $files[] = $name;
        }

        $matches = $this->matchAll($files);

        return view('admin.products.bulk-photos-preview', compact('token', 'files', 'matches'));
    }

    /** Serve a staged file so the review grid can thumbnail it. */
    public function file(string $token, string $name)
    {
        if (! preg_match('/^[A-Za-z0-9]{32}$/', $token)) {
            abort(404);
        }
        $path = 'bulk-photos/'.$token.'/'.basename($name);
        if (! Storage::exists($path)) {
            abort(404);
        }

        return response()->file(Storage::path($path));
    }

    /** Live search for manually assigning an unmatched photo. */
    public function search(Request $request)
    {
        $q = mb_strtolower(trim((string) $request->input('q', '')));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $like = '%'.str_replace(['%', '_'], '', $q).'%';

        return response()->json(
            ProductVariant::with('product:id,name')
                ->where(function ($w) use ($like) {
                    $w->whereRaw('LOWER(sku) LIKE ?', [$like])
                        ->orWhereHas('product', fn ($p) => $p->whereRaw('LOWER(name) LIKE ?', [$like]));
                })
                ->orderBy('id')
                ->limit(15)
                ->get()
                ->map(fn ($v) => [
                    'variant_id' => $v->id,
                    'product_id' => $v->product_id,
                    'sku' => $v->sku,
                    'product' => $v->product?->name,
                    'is_default' => (bool) $v->is_default,
                    'label' => ($v->sku ? $v->sku.' — ' : '').($v->product?->name ?? '').($v->is_default ? ' (default)' : ''),
                ])
                ->values()
        );
    }

    /** Convert staged files to webp and attach them. */
    public function confirm(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:32',
            'items' => 'required|array',
            'items.*.file' => 'required|string',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.target' => 'required|in:hero,variant',
            'items.*.also_hero' => 'nullable|boolean',
        ]);

        $dir = 'bulk-photos/'.$request->token;
        if (! Storage::exists($dir)) {
            return redirect()->route('products.bulkPhotos')->with('error', 'Upload expired — please drop the photos again.');
        }

        $saved = 0;
        $skipped = [];
        DB::transaction(function () use ($request, $dir, &$saved, &$skipped) {
            foreach ($request->items as $item) {
                $source = Storage::path($dir.'/'.basename($item['file']));
                if (! is_file($source)) {
                    $skipped[] = $item['file'];

                    continue;
                }
                if ($item['target'] === 'hero') {
                    $product = Product::findOrFail($item['product_id']);
                    $this->deleteFile($product->hero_image);
                    $product->hero_image = $this->storeWebp($source, 'uploads/products/', 1600, 75);
                    $product->save();
                    $saved++;
                } else {
                    $variant = ProductVariant::with('product')->findOrFail($item['variant_id']);
                    if ((int) $variant->product_id !== (int) $item['product_id']) {
                        $skipped[] = $item['file'].' (variant does not belong to the chosen product)';

                        continue;
                    }
                    $this->deleteFile($variant->image);
                    $variant->image = $this->storeWebp($source, 'uploads/products/variants/', 800, 75);
                    $variant->save();
                    $saved++;
                    if (! empty($item['also_hero']) && $variant->is_default) {
                        $product = $variant->product;
                        $this->deleteFile($product->hero_image);
                        $product->hero_image = $this->storeWebp($source, 'uploads/products/', 1600, 75);
                        $product->save();
                    }
                }
            }
        });
        Storage::deleteDirectory($dir);

        $msg = "Bulk photos saved: {$saved} photo(s) attached.";
        if ($skipped) {
            $msg .= ' Skipped: '.implode(', ', $skipped);
        }

        return redirect()->route('products.index')->with('success', $msg);
    }

    /**
     * Match staged filenames to variants/products.
     * Exact SKU or slug wins; otherwise the longest SKU/slug contained in
     * the filename (separators normalised) so EGF-1981-57582 beats EGF-1981.
     */
    private function matchAll(array $files): array
    {
        $variants = ProductVariant::with('product')->whereNotNull('sku')->where('sku', '!=', '')->get();
        $bySku = [];
        foreach ($variants as $v) {
            $bySku[mb_strtolower($v->sku)] = $v;
        }
        $skus = array_keys($bySku);
        usort($skus, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        $bySlug = [];
        foreach (Product::orderBy('id')->get() as $p) {
            $bySlug[mb_strtolower($p->slug)] = $p;
        }
        $slugs = array_keys($bySlug);
        usort($slugs, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        $out = [];
        foreach ($files as $file) {
            $stem = mb_strtolower(pathinfo($file, PATHINFO_FILENAME));
            $norm = str_replace([' ', '_'], '-', $stem);
            $match = ['file' => $file, 'variant_id' => null, 'product_id' => null, 'product' => null, 'sku' => null, 'target' => 'variant', 'also_hero' => false, 'method' => null, 'current_image' => null];

            $variant = $bySku[$stem] ?? $bySku[$norm] ?? null;
            $method = $variant ? 'exact-sku' : null;
            if (! $variant) {
                foreach ([$stem, $norm] as $haystack) {
                    foreach ($skus as $sku) {
                        if (mb_strlen($sku) >= 3 && str_contains($haystack, $sku)) {
                            $variant = $bySku[$sku];
                            $method = 'contains-sku';

                            break 2;
                        }
                    }
                }
            }
            if ($variant) {
                $match['variant_id'] = $variant->id;
                $match['product_id'] = $variant->product_id;
                $match['product'] = $variant->product?->name;
                $match['sku'] = $variant->sku;
                $match['target'] = 'variant';
                $match['also_hero'] = (bool) $variant->is_default;
                $match['method'] = $method;
                $match['current_image'] = $variant->image;
            } else {
                $product = $bySlug[$stem] ?? $bySlug[$norm] ?? null;
                $method = $product ? 'exact-slug' : null;
                if (! $product) {
                    foreach ([$stem, $norm] as $haystack) {
                        foreach ($slugs as $slug) {
                            if (mb_strlen($slug) >= 4 && str_contains($haystack, $slug)) {
                                $product = $bySlug[$slug];
                                $method = 'contains-slug';

                                break 2;
                            }
                        }
                    }
                }
                if ($product) {
                    $match['product_id'] = $product->id;
                    $match['product'] = $product->name;
                    $match['target'] = 'hero';
                    $match['method'] = $method;
                    $match['current_image'] = $product->hero_image;
                }
            }
            $out[] = $match;
        }

        return $out;
    }

    private function uniqueName(string $dir, string $original): string
    {
        $base = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($original));
        $base = $base !== '' ? $base : 'photo.jpg';
        $stem = pathinfo($base, PATHINFO_FILENAME);
        $ext = pathinfo($base, PATHINFO_EXTENSION);
        $name = $base;
        $i = 1;
        while (Storage::exists($dir.'/'.$name)) {
            $name = $stem.'-'.$i.'.'.$ext;
            $i++;
        }

        return $name;
    }

    private function storeWebp(string $source, string $dir, int $width, int $quality): string
    {
        $path = public_path($dir);
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $name = mt_rand(10000000, 99999999).'.webp';
        Image::make($source)->resize($width, null, function ($c) {
            $c->aspectRatio();
            $c->upsize();
        })->encode('webp', $quality)->save($path.$name);

        return '/'.$dir.$name;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && $path !== 'placeholder.webp' && file_exists(public_path($path))) {
            @unlink(public_path($path));
        }
    }
}
