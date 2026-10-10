<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Facades\Image as ImageFacade;

class LocalizeProductImages extends Command
{
    protected $signature = 'products:localize-images
        {--dry-run : List what would change without downloading or updating}
        {--limit= : Only process this many distinct URLs}
        {--host=evergreenfoods.co.uk : Only localize URLs containing this host (empty = all http URLs)}';

    protected $description = 'Download remote supplier product images into public/uploads/products and point the DB at the local copies.';

    public function handle(): int
    {
        $host = trim((string) $this->option('host'));
        $matchesHost = fn (?string $url): bool => is_string($url)
            && str_starts_with($url, 'http')
            && ($host === '' || str_contains(strtolower($url), strtolower($host)));

        $heroRows = DB::table('products')->where('hero_image', 'like', 'http%')->get(['id', 'hero_image']);
        $variantRows = DB::table('product_variants')->where('image', 'like', 'http%')->get(['id', 'image']);
        $galleryRows = DB::table('product_images')->where('image', 'like', 'http%')->get(['id', 'image']);

        $byUrl = [];
        foreach ($heroRows as $r) {
            if ($matchesHost($r->hero_image)) {
                $byUrl[$r->hero_image]['products'][] = $r->id;
            }
        }
        foreach ($variantRows as $r) {
            if ($matchesHost($r->image)) {
                $byUrl[$r->image]['variants'][] = $r->id;
            }
        }
        foreach ($galleryRows as $r) {
            if ($matchesHost($r->image)) {
                $byUrl[$r->image]['gallery'][] = $r->id;
            }
        }

        if (empty($byUrl)) {
            $this->info('No remote product images to localize.');

            return self::SUCCESS;
        }

        $urls = array_keys($byUrl);
        if ($this->option('limit')) {
            $urls = array_slice($urls, 0, (int) $this->option('limit'));
        }

        $this->info(count($urls).' distinct remote image(s) to localize.');

        if ($this->option('dry-run')) {
            foreach (array_slice($urls, 0, 20) as $url) {
                $use = $byUrl[$url];
                $this->line($url.'  (products: '.count($use['products'] ?? []).', variants: '.count($use['variants'] ?? []).', gallery: '.count($use['gallery'] ?? []).')');
            }
            if (count($urls) > 20) {
                $this->line('... and '.(count($urls) - 20).' more.');
            }

            return self::SUCCESS;
        }

        $dir = public_path('uploads/products/');
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $ok = 0;
        $failed = [];
        $updatedProducts = 0;
        $updatedVariants = 0;
        $updatedGallery = 0;

        foreach ($urls as $i => $url) {
            $local = '/uploads/products/evergreen-'.md5($url).'.webp';
            $absolute = public_path($local);

            if (! file_exists($absolute)) {
                try {
                    $response = Http::timeout(60)
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
                        ->get($url);
                } catch (\Throwable $e) {
                    $failed[] = $url.' ('.$e->getMessage().')';

                    continue;
                }
                if (! $response->successful() || ! $response->body()) {
                    $failed[] = $url.' (HTTP '.$response->status().')';

                    continue;
                }
                try {
                    ImageFacade::make($response->body())->resize(1600, null, function ($c) {
                        $c->aspectRatio();
                        $c->upsize();
                    })->encode('webp', 75)->save($absolute);
                } catch (\Throwable $e) {
                    @unlink($absolute);
                    $failed[] = $url.' (convert failed: '.$e->getMessage().')';

                    continue;
                }
            }

            $use = $byUrl[$url];
            if (! empty($use['products'])) {
                $updatedProducts += DB::table('products')->whereIn('id', $use['products'])->where('hero_image', $url)->update(['hero_image' => $local]);
            }
            if (! empty($use['variants'])) {
                $updatedVariants += DB::table('product_variants')->whereIn('id', $use['variants'])->where('image', $url)->update(['image' => $local]);
            }
            if (! empty($use['gallery'])) {
                $updatedGallery += DB::table('product_images')->whereIn('id', $use['gallery'])->where('image', $url)->update(['image' => $local]);
            }
            $ok++;

            if (($i + 1) % 50 === 0) {
                $this->info(($i + 1).'/'.count($urls).' processed...');
            }
        }

        $this->info("Localized {$ok}/".count($urls).' URLs. Products updated: '.$updatedProducts.', variants updated: '.$updatedVariants.', gallery updated: '.$updatedGallery.'.');
        if ($failed) {
            $this->warn(count($failed).' URL(s) failed (DB left pointing at the remote copy):');
            foreach (array_slice($failed, 0, 20) as $f) {
                $this->line(' - '.$f);
            }
        }

        return empty($failed) ? self::SUCCESS : self::FAILURE;
    }
}
