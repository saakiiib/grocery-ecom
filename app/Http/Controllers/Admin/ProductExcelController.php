<?php

namespace App\Http\Controllers\Admin;

use App\Excel\ProductsExport;
use App\Excel\ProductsImport;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductExcelController extends Controller
{
    public function export()
    {
        $book = ProductsExport::build();
        $name = 'products-'.now()->format('Ymd-His').'.xlsx';
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($book))->save($tmp);

        return response()->download($tmp, $name)->deleteFileAfterSend(true);
    }

    /**
     * Empty hero/ + variants/ folders + README + manifest of every expected
     * filename. Supplier fills the folders once, zips, uploads with the Excel.
     */
    public function imageTemplate()
    {
        $products = Product::with('variants')->orderBy('sort_order')->orderByDesc('id')->get();
        $lines = ['type,product,key,expected_filename'];
        foreach ($products as $p) {
            $lines[] = 'Hero,"'.str_replace('"', '""', $p->name)."\",{$p->slug},".ProductsImport::expectedHeroName($p->slug);
            foreach ($p->variants as $v) {
                if (! $v->sku) {
                    continue;
                }
                $lines[] = 'Variant,"'.str_replace('"', '""', $p->name)."\",{$v->sku},".ProductsImport::expectedVariantName($v->sku);
            }
        }

        $tmp = tempnam(sys_get_temp_dir(), 'imgtpl');
        $zipPath = $tmp.'.zip';
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addEmptyDir('hero');
        $zip->addEmptyDir('variants');
        $zip->addFromString('README.txt', "Drop one photo per slot, then zip the hero/ and variants/ folders and upload the ZIP together with the Excel.\n\n- Hero photos go in hero/ named exactly: PRODUCT-SLUG.jpg (example: lamb-leg-bone-in.jpg — see manifest.csv)\n- Variant photos go in variants/ named exactly: SKU.jpg (example: EGF89913.jpg)\n- jpg, png or webp. Match is by filename only (case-insensitive).\n- Files with no matching slot are reported and ignored.\n");
        $zip->addFromString('manifest.csv', implode("\n", $lines)."\n");
        $zip->close();
        @unlink($tmp);

        return response()->download($zipPath, 'product-images-template.zip')->deleteFileAfterSend(true);
    }

    /** Upload → parse → full-page preview (no writes yet). */
    public function importPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'images' => 'nullable|file|mimes:zip|max:102400',
        ]);

        $token = Str::random(32);
        $dir = "imports/{$token}";
        $request->file('file')->storeAs($dir, 'products.'.$request->file('file')->getClientOriginalExtension());

        $imageDir = null;
        if ($request->hasFile('images')) {
            $imageDir = Storage::path("{$dir}/images");
            @mkdir($imageDir, 0755, true);
            $zip = new \ZipArchive;
            if ($zip->open($request->file('images')->getRealPath()) !== true) {
                return redirect()->route('products.index')->with('error', 'The images ZIP could not be opened.');
            }
            $zip->extractTo($imageDir);
            $zip->close();
        }

        $excel = collect(Storage::files($dir))->first(fn ($f) => str_starts_with(basename($f), 'products.'));
        $result = ProductsImport::parse(Storage::path($excel), $imageDir);

        return view('admin.products.import-preview', [
            'token' => $token,
            'rows' => collect($result['rows']),
            'errors' => $result['errors'],
            'stats' => $result['stats'],
            'images' => $result['images'],
            'hasZip' => (bool) $imageDir,
        ]);
    }

    /** Confirm the previewed file: commit everything in one transaction. */
    public function importConfirm(Request $request)
    {
        $request->validate(['token' => 'required|string|size:32']);

        $dir = "imports/{$request->token}";
        if (! Storage::exists($dir)) {
            return redirect()->route('products.index')->with('error', 'Import expired — please upload again.');
        }

        $excel = collect(Storage::files($dir))->first(fn ($f) => str_starts_with(basename($f), 'products.'));
        $imageDir = Storage::exists("{$dir}/images") ? Storage::path("{$dir}/images") : null;
        $result = ProductsImport::parse(Storage::path($excel), $imageDir);

        // Global (row 0) errors mean whole products are ambiguous — refuse, don't half-commit.
        $global = collect($result['errors'])->where('row', 0)->values();
        if ($global->isNotEmpty()) {
            return redirect()->route('products.index')->with('error', 'Import blocked — fix and re-upload: '.$global->map(fn ($e) => $e['message'])->join(' '));
        }

        $valid = collect($result['rows']);
        $summary = ProductsImport::commit($valid->all(), $request->boolean('disable_missing'), $imageDir);
        Storage::deleteDirectory($dir);

        $msg = "Import complete: {$summary['products_created']} products created, {$summary['products_updated']} updated, {$summary['variants_created']} variants created, {$summary['variants_updated']} updated, {$summary['images_saved']} images saved.";
        if ($result['errors']) {
            $msg .= ' Skipped '.count($result['errors']).' invalid row(s).';
        }
        if ($summary['image_warnings']) {
            $msg .= ' Image notes: '.implode(' ', $summary['image_warnings']);
        }

        return redirect()->route('products.index')->with('success', $msg);
    }
}
