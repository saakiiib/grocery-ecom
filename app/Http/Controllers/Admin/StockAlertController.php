<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockAlert;
use Illuminate\Http\RedirectResponse;

class StockAlertController extends Controller
{
    public function index()
    {
        $alerts = StockAlert::with(['product:id,name,slug', 'variant:id,sku'])
            ->orderByDesc('created_at')->paginate(25);

        return view('admin.stock-alerts.index', compact('alerts'));
    }

    public function destroy($id): RedirectResponse
    {
        StockAlert::findOrFail($id)->delete();

        return redirect()->route('stock-alerts.index')->with('status', 'Alert deleted.');
    }
}
