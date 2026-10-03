<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BogoOffer;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BogoController extends Controller
{
    public function index()
    {
        $offers = BogoOffer::with(['product:id,name', 'variant:id,product_id,sku', 'offer:id,name'])
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        return view('admin.bogo.index', compact('offers'));
    }

    public function create()
    {
        $products = Product::where('status', true)->orderBy('name')->get(['id', 'name']);
        $offers = Offer::orderBy('sort_order')->orderBy('id')->get(['id', 'name']);

        return view('admin.bogo.manage', ['offer' => new BogoOffer(['buy_qty' => 2, 'free_qty' => 1, 'status' => true]), 'products' => $products, 'offers' => $offers]);
    }

    public function store(Request $request): RedirectResponse
    {
        BogoOffer::create($this->validated($request) + ['sort_order' => (int) BogoOffer::max('sort_order') + 1]);

        return redirect()->route('bogo.index')->with('status', 'BOGO offer added.');
    }

    public function edit(int $id)
    {
        $offer = BogoOffer::findOrFail($id);
        $products = Product::where('status', true)->orderBy('name')->get(['id', 'name']);
        $offers = Offer::orderBy('sort_order')->orderBy('id')->get(['id', 'name']);

        return view('admin.bogo.manage', compact('offer', 'products', 'offers'));
    }

    public function update(Request $request): RedirectResponse
    {
        $offer = BogoOffer::findOrFail($request->input('id'));
        $offer->update($this->validated($request));

        return redirect()->route('bogo.index')->with('status', 'BOGO offer saved.');
    }

    public function destroy(int $id): RedirectResponse
    {
        BogoOffer::findOrFail($id)->delete();

        return redirect()->route('bogo.index')->with('status', 'BOGO offer deleted.');
    }

    public function toggleStatus(Request $request)
    {
        $offer = BogoOffer::findOrFail($request->input('id'));
        $offer->status = ! $offer->status;
        $offer->save();

        return response()->json(['success' => true]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'offer_id' => 'nullable|integer|exists:offers,id',
            'buy_qty' => 'required|integer|min:1|max:99',
            'free_qty' => 'required|integer|min:1|max:99',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
        ]);

        if (! empty($data['product_variant_id'])) {
            $belongs = ProductVariant::where('id', $data['product_variant_id'])
                ->where('product_id', $data['product_id'])->exists();
            if (! $belongs) {
                abort(422, 'That pack does not belong to the chosen product.');
            }
        }

        return [
            'product_id' => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'offer_id' => $data['offer_id'] ?? null,
            'buy_qty' => $data['buy_qty'],
            'free_qty' => $data['free_qty'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'status' => $request->boolean('is_active'),
        ];
    }
}
