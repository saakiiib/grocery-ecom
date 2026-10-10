<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Paste-ready diagnostic brief for getting help from another AI or developer.
 * Read-only: never writes, migrates or deletes anything.
 */
class ProjectBriefCommand extends Command
{
    protected $signature = 'project:brief {--errors=5 : how many recent log errors to include}';

    protected $description = 'Print a paste-ready diagnostic brief (versions, image stats, routes, recent errors).';

    public function handle(): int
    {
        $out = [];
        $out[] = '# Grocery backend — diagnostic brief ('.now()->toDateTimeString().')';
        $out[] = '';
        $out[] = '## Environment';
        $out[] = '- PHP '.PHP_VERSION.' | Laravel '.app()->version().' | DB '.config('database.default');
        $out[] = '- intervention/image '.($this->pkgVersion('intervention/image') ?? '?').' | phpspreadsheet '.($this->pkgVersion('phpoffice/phpspreadsheet') ?? '?');
        $out[] = '- upload_max_filesize='.ini_get('upload_max_filesize').' post_max_size='.ini_get('post_max_size').' max_file_uploads='.ini_get('max_file_uploads');
        foreach (['public/uploads/products', 'public/uploads/products/variants', 'public/uploads/products/gallery', 'storage/app'] as $d) {
            $p = base_path($d);
            $out[] = '- '.(is_dir($p) ? (is_writable($p) ? 'writable' : 'NOT WRITABLE') : 'MISSING').' '.$d;
        }
        $out[] = '';
        $out[] = '## Catalog / images';
        try {
            $out[] = '- products='.Product::count().' variants='.ProductVariant::count();
            $out[] = '- hero remote(http)='.Product::where('hero_image', 'like', 'http%')->count().' variant remote(http)='.ProductVariant::where('image', 'like', 'http%')->count();
            $out[] = '- hero empty='.Product::whereNull('hero_image')->count().' variant empty='.ProductVariant::whereNull('image')->count();
            $out[] = '- products without variants='.Product::whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('product_variants')->whereColumn('product_variants.product_id', 'products.id'))->count();
        } catch (\Throwable $e) {
            $out[] = '- DB unreachable: '.$e->getMessage();
        }
        $out[] = '';
        $out[] = '## Product routes';
        foreach (Route::getRoutes() as $r) {
            $name = $r->getName() ?? '';
            if (str_starts_with($name, 'products.') || str_starts_with($name, 'product-')) {
                $out[] = '- '.implode('|', $r->methods()).' '.$r->uri().' ('.$name.')';
            }
        }
        $out[] = '';
        $out[] = '## Recent errors';
        $log = storage_path('logs/laravel.log');
        if (! is_file($log)) {
            $out[] = '- no log file';
        } else {
            preg_match_all('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): ([^\{]+)/', file_get_contents($log), $m, PREG_SET_ORDER);
            $last = array_slice($m, -1 * max(1, (int) $this->option('errors')));
            foreach ($last as $e) {
                $out[] = '- '.$e[1].' '.$e[2].': '.mb_substr(trim($e[3]), 0, 300);
            }
        }

        $this->line(implode("\n", $out));

        return self::SUCCESS;
    }

    private function pkgVersion(string $name): ?string
    {
        $lock = json_decode(@file_get_contents(base_path('composer.lock')), true);
        foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $p) {
            if (($p['name'] ?? '') === $name) {
                return $p['version'] ?? null;
            }
        }

        return null;
    }
}
