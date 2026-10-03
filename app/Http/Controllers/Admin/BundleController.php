<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BundleOffer;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    public function index()
    {
        $bundles = BundleOffer::with(['offer:id,name'])->withCount(['categories', 'variants'])
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        return view('admin.bundles.index', compact('bundles'));
    }

    public function create()
    {
        return view('admin.bundles.manage', [
            'bundle' => new BundleOffer(['required_qty' => 3, 'status' => true]),
            ...$this->formData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $bundle = BundleOffer::create([...$data['fields'], 'sort_order' => (int) BundleOffer::max('sort_order') + 1]);
        $bundle->categories()->sync($data['categories']);
        $bundle->variants()->sync($data['variants']);

        return redirect()->route('bundles.index')->with('status', 'Bundle added.');
    }

    public function edit(int $id)
    {
        $bundle = BundleOffer::with(['categories:id', 'variants:id'])->findOrFail($id);

        return view('admin.bundles.manage', ['bundle' => $bundle, ...$this->formData()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $bundle = BundleOffer::findOrFail($request->input('id'));
        $data = $this->validated($request);
        $bundle->update($data['fields']);
        $bundle->categories()->sync($data['categories']);
        $bundle->variants()->sync($data['variants']);

        return redirect()->route('bundles.index')->with('status', 'Bundle saved.');
    }

    public function destroy(int $id): RedirectResponse
    {
        BundleOffer::findOrFail($id)->delete();

        return redirect()->route('bundles.index')->with('status', 'Bundle deleted.');
    }

    public function toggleStatus(Request $request)
    {
        $bundle = BundleOffer::findOrFail($request->input('id'));
        $bundle->status = ! $bundle->status;
        $bundle->save();

        return response()->json(['success' => true]);
    }

    /** @return array{categories: mixed, products: mixed} */
    private function formData(): array
    {
        $categories = Category::with('children:id,parent_id,name')
            ->whereNull('parent_id')->where('status', true)
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'name']);
        $products = Product::with(['variants' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->where('status', true)->orderBy('name')
            ->get(['id', 'name']);
        $offers = Offer::orderBy('sort_order')->orderBy('id')->get(['id', 'name']);

        return compact('categories', 'products', 'offers');
    }

    /** @return array{fields: array, categories: array, variants: array} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'required_qty' => 'required|integer|min:2|max:99',
            'bundle_price' => 'required|numeric|min:0.01|max:999999',
            'offer_id' => 'nullable|integer|exists:offers,id',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
            'variants' => 'nullable|array',
            'variants.*' => 'integer|exists:product_variants,id',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($data['categories']) && empty($data['variants'])) {
            abort(422, 'Pick at least one category or pack for the pool.');
        }

        return [
            'fields' => [
                'name' => $data['name'],
                'offer_id' => $data['offer_id'] ?? null,
                'required_qty' => $data['required_qty'],
                'bundle_price' => $data['bundle_price'],
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'status' => $request->boolean('is_active'),
            ],
            'categories' => array_values(array_unique(array_map('intval', $data['categories'] ?? []))),
            'variants' => array_values(array_unique(array_map('intval', $data['variants'] ?? []))),
        ];
    }
}
