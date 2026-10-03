<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function index()
    {
        $sales = FlashSale::with(['product:id,name', 'variant:id,product_id,sku', 'offer:id,name'])
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        return view('admin.flash.index', compact('sales'));
    }

    public function create()
    {
        $products = Product::where('status', true)->orderBy('name')->get(['id', 'name']);
        $offers = Offer::orderBy('sort_order')->orderBy('id')->get(['id', 'name']);

        return view('admin.flash.manage', ['sale' => new FlashSale(['status' => true]), 'products' => $products, 'offers' => $offers]);
    }

    public function store(Request $request): RedirectResponse
    {
        FlashSale::create($this->validated($request) + ['sort_order' => (int) FlashSale::max('sort_order') + 1]);

        return redirect()->route('flash.index')->with('status', 'Flash sale added.');
    }

    public function edit(int $id)
    {
        $sale = FlashSale::findOrFail($id);
        $products = Product::where('status', true)->orderBy('name')->get(['id', 'name']);
        $offers = Offer::orderBy('sort_order')->orderBy('id')->get(['id', 'name']);

        return view('admin.flash.manage', compact('sale', 'products', 'offers'));
    }

    public function update(Request $request): RedirectResponse
    {
        $sale = FlashSale::findOrFail($request->input('id'));
        $sale->update($this->validated($request));

        return redirect()->route('flash.index')->with('status', 'Flash sale saved.');
    }

    public function destroy(int $id): RedirectResponse
    {
        FlashSale::findOrFail($id)->delete();

        return redirect()->route('flash.index')->with('status', 'Flash sale deleted.');
    }

    public function toggleStatus(Request $request)
    {
        $sale = FlashSale::findOrFail($request->input('id'));
        $sale->status = ! $sale->status;
        $sale->save();

        return response()->json(['success' => true]);
    }

    /** Promote a winning flash into a permanent offer price. */
    public function makePermanent(int $id): RedirectResponse
    {
        $sale = FlashSale::findOrFail($id);
        if ($sale->product_variant_id) {
            ProductVariant::where('id', $sale->product_variant_id)->update(['offer_price' => $sale->promo_price]);
        } else {
            ProductVariant::where('product_id', $sale->product_id)->update(['offer_price' => $sale->promo_price]);
        }

        return redirect()->route('flash.index')->with('status', 'Flash price is now the permanent offer price.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'offer_id' => 'nullable|integer|exists:offers,id',
            'promo_price' => 'required|numeric|min:0.01|max:999999',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'nullable|boolean',
        ]);

        $variants = ProductVariant::where('product_id', $data['product_id']);
        if (! empty($data['product_variant_id'])) {
            $variant = (clone $variants)->where('id', $data['product_variant_id'])->first();
            if (! $variant) {
                abort(422, 'That pack does not belong to the chosen product.');
            }
            $floor = (float) $variant->mrp;
        } else {
            $floor = (float) $variants->min('mrp');
        }
        if ((float) $data['promo_price'] >= $floor) {
            abort(422, 'The flash price must sit below the shelf price (£'.number_format($floor, 2).').');
        }

        return [
            'product_id' => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'offer_id' => $data['offer_id'] ?? null,
            'promo_price' => $data['promo_price'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'status' => $request->boolean('is_active'),
        ];
    }
}
