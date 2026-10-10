<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image as ImageFacade;

/**
 * Attach product photos from a folder — no browser, no Excel, no ZIP.
 *
 *   php artisan products:attach-photos C:\photos              (preview only)
 *   php artisan products:attach-photos C:\photos --commit     (save for real)
 *
 * Name each file with its SKU (EGF89913.jpg) or the product slug for a
 * main photo. Run without --commit first to see what matches what.
 */
class AttachProductPhotosCommand extends Command
{
    protected $signature = 'products:attach-photos
        {dir : Folder with product photos (jpg/png/webp, subfolders ok)}
        {--commit : Actually convert and save; without it only reports matches}
        {--limit= : Only process this many files}
        {--hero : Also update the main photo when the matched size is the default}';

    protected $description = 'Match photo files by SKU/slug filename and attach them as variant or main photos.';

    public function handle(): int
    {
        $dir = $this->argument('dir');
        if (! is_dir($dir)) {
            $this->error("Not a folder: {$dir}");

            return self::FAILURE;
        }

        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && in_array(mb_strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        if ($this->option('limit')) {
            $files = array_slice($files, 0, (int) $this->option('limit'));
        }

        if (empty($files)) {
            $this->info('No jpg/png/webp photos found in '.$dir);

            return self::SUCCESS;
        }

        [$bySku, $skus, $bySlug, $slugs] = $this->catalog();
        $matched = 0;
        $unmatched = [];
        $jobs = [];
        foreach ($files as $path) {
            $hit = $this->match(basename($path), $bySku, $skus, $bySlug, $slugs);
            if (! $hit) {
                $unmatched[] = basename($path);
                $this->line(basename($path).'  →  NO MATCH (rename with the SKU, e.g. EGF89913.jpg)');
            } else {
                $matched++;
                $jobs[] = ['path' => $path] + $hit;
                $this->line(basename($path).'  →  '.($hit['sku'] ? $hit['sku'].' — ' : '').$hit['product'].'  ('.$hit['kind'].', '.$hit['method'].')');
            }
        }

        $this->info(count($files).' file(s): '.$matched.' matched, '.count($unmatched).' unmatched.');

        if (! $this->option('commit')) {
            $this->comment('Preview only — nothing saved. Re-run with --commit to attach.');

            return self::SUCCESS;
        }

        $saved = 0;
        DB::transaction(function () use ($jobs, &$saved) {
            foreach ($jobs as $job) {
                if ($job['kind'] === 'hero') {
                    $product = Product::findOrFail($job['product_id']);
                    $this->deleteFile($product->hero_image);
                    $product->hero_image = $this->storeWebp($job['path'], 'uploads/products/', 1600, 75);
                    $product->save();
                    $saved++;
                } else {
                    $variant = ProductVariant::findOrFail($job['variant_id']);
                    $this->deleteFile($variant->image);
                    $variant->image = $this->storeWebp($job['path'], 'uploads/products/variants/', 800, 75);
                    $variant->save();
                    $saved++;
                    if ($this->option('hero') && $variant->is_default) {
                        $product = $variant->product;
                        $this->deleteFile($product->hero_image);
                        $product->hero_image = $this->storeWebp($job['path'], 'uploads/products/', 1600, 75);
                        $product->save();
                    }
                }
            }
        });

        $this->info("Attached {$saved} photo(s).".($unmatched ? ' Unmatched: '.implode(', ', $unmatched) : ''));

        return self::SUCCESS;
    }

    /** @return array{0: array, 1: array, 2: array, 3: array} */
    private function catalog(): array
    {
        $bySku = [];
        foreach (ProductVariant::with('product')->whereNotNull('sku')->where('sku', '!=', '')->get() as $v) {
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

        return [$bySku, $skus, $bySlug, $slugs];
    }

    private function match(string $filename, array $bySku, array $skus, array $bySlug, array $slugs): ?array
    {
        $stem = mb_strtolower(pathinfo($filename, PATHINFO_FILENAME));
        $norm = str_replace([' ', '_'], '-', $stem);

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
            return ['kind' => 'variant', 'variant_id' => $variant->id, 'product_id' => $variant->product_id, 'product' => $variant->product?->name, 'sku' => $variant->sku, 'method' => $method];
        }

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

        return $product ? ['kind' => 'hero', 'variant_id' => null, 'product_id' => $product->id, 'product' => $product->name, 'sku' => null, 'method' => $method] : null;
    }

    private function storeWebp(string $source, string $dir, int $width, int $quality): string
    {
        $path = public_path($dir);
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $name = mt_rand(10000000, 99999999).'.webp';
        ImageFacade::make($source)->resize($width, null, function ($c) {
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
