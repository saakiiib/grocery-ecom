<?php

namespace App\Http\Controllers\Admin;

use App\Excel\ProductsExport;
use App\Excel\ProductsImport;
use App\Http\Controllers\Controller;
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

    /** Upload → parse → full-page preview (no writes yet). Images are managed on the Bulk Photos page, not here. */
    public function importPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $token = Str::random(32);
        $dir = "imports/{$token}";
        $request->file('file')->storeAs($dir, 'products.'.$request->file('file')->getClientOriginalExtension());

        $excel = collect(Storage::files($dir))->first(fn ($f) => str_starts_with(basename($f), 'products.'));
        $result = ProductsImport::parse(Storage::path($excel));

        return view('admin.products.import-preview', [
            'token' => $token,
            'rows' => collect($result['rows']),
            'errors' => $result['errors'],
            'stats' => $result['stats'],
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
        $result = ProductsImport::parse(Storage::path($excel));

        // Global (row 0) errors mean whole products are ambiguous — refuse, don't half-commit.
        $global = collect($result['errors'])->where('row', 0)->values();
        if ($global->isNotEmpty()) {
            return redirect()->route('products.index')->with('error', 'Import blocked — fix and re-upload: '.$global->map(fn ($e) => $e['message'])->join(' '));
        }

        $valid = collect($result['rows']);
        $summary = ProductsImport::commit($valid->all(), $request->boolean('disable_missing'));
        Storage::deleteDirectory($dir);

        $msg = "Import complete: {$summary['products_created']} products created, {$summary['products_updated']} updated, {$summary['variants_created']} variants created, {$summary['variants_updated']} updated.";
        if ($result['errors']) {
            $msg .= ' Skipped '.count($result['errors']).' invalid row(s).';
        }

        return redirect()->route('products.index')->with('success', $msg);
    }
}
