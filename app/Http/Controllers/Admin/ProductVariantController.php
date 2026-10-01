<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OptionValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class ProductVariantController extends Controller
{
    public function list($productId)
    {
        return response()->json(
            ProductVariant::with('values.group')
                ->where('product_id', $productId)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($v) => [...$v->toArray(), 'combination' => $v->combinationLabel()])
        );
    }

    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $request->validate([
            'sku' => 'nullable|string|max:100|unique:product_variants,sku',
            'mrp' => 'required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0|lte:mrp',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'value_ids' => 'nullable|array',
            'value_ids.*' => 'exists:option_values,id',
        ]);
        $this->assertOneValuePerGroup($request->value_ids ?? []);

        $variant = new ProductVariant($request->only(['sku', 'mrp', 'offer_price']));
        $variant->product_id = $product->id;
        $variant->in_stock = $request->boolean('in_stock', true);
        $variant->status = true;
        $variant->sort_order = (int) (ProductVariant::where('product_id', $product->id)->max('sort_order') ?? 0) + 1;
        // First variant of a product becomes the default automatically.
        $variant->is_default = ! ProductVariant::where('product_id', $product->id)->exists();
        if ($request->hasFile('image')) {
            $variant->image = $this->storeWebp($request->file('image'), 'uploads/products/variants/', 800, 75);
        }
        $variant->save();
        $variant->values()->sync($request->value_ids ?? []);

        return response()->json(['message' => 'Variant added', 'data' => $variant]);
    }

    public function update(Request $request, $id)
    {
        $variant = ProductVariant::findOrFail($id);
        $request->validate([
            'sku' => 'nullable|string|max:100|unique:product_variants,sku,'.$variant->id,
            'mrp' => 'required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0|lte:mrp',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'value_ids' => 'nullable|array',
            'value_ids.*' => 'exists:option_values,id',
        ]);
        $this->assertOneValuePerGroup($request->value_ids ?? []);

        $variant->fill($request->only(['sku', 'mrp', 'offer_price']));
        if ($request->has('in_stock')) {
            $variant->in_stock = $request->boolean('in_stock');
        }
        if ($request->has('status')) {
            $variant->status = $request->boolean('status');
        }
        if ($request->hasFile('image')) {
            $this->deleteFile($variant->image);
            $variant->image = $this->storeWebp($request->file('image'), 'uploads/products/variants/', 800, 75);
        }
        $variant->save();
        if ($request->has('value_ids')) {
            $variant->values()->sync($request->value_ids ?? []);
        }

        return response()->json(['message' => 'Variant updated']);
    }

    public function destroy($id)
    {
        $variant = ProductVariant::findOrFail($id);
        $wasDefault = $variant->is_default;
        $this->deleteFile($variant->image);
        $variant->delete();

        // Never leave a product without a default: promote the first remaining row.
        if ($wasDefault) {
            $next = ProductVariant::where('product_id', $variant->product_id)->orderBy('sort_order')->first();
            $next?->update(['is_default' => true]);
        }

        return response()->json(['message' => 'Variant deleted']);
    }

    public function setDefault($id)
    {
        $variant = ProductVariant::findOrFail($id);
        ProductVariant::where('product_id', $variant->product_id)->update(['is_default' => false]);
        $variant->update(['is_default' => true]);

        return response()->json(['message' => 'Default variant updated']);
    }

    public function toggleStock(Request $request)
    {
        $variant = ProductVariant::findOrFail($request->id);
        $variant->update(['in_stock' => ! $variant->in_stock]);

        return response()->json(['message' => 'Stock updated']);
    }

    public function removeImage($id)
    {
        $variant = ProductVariant::findOrFail($id);
        $this->deleteFile($variant->image);
        $variant->update(['image' => null]);

        return response()->json(['message' => 'Variant image removed']);
    }

    /**
     * Override the inherited category template for one product.
     * Empty list = clear overrides and inherit from the category again.
     */
    public function syncGroups(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $request->validate([
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:option_groups,id',
        ]);

        $sync = [];
        foreach (array_values($request->group_ids ?? []) as $i => $gid) {
            $sync[$gid] = ['sort_order' => $i];
        }
        $product->optionGroups()->sync($sync);

        return response()->json(['message' => 'Option groups updated']);
    }

    /** A variant may hold at most one value per option group. */
    private function assertOneValuePerGroup(array $valueIds): void
    {
        if (empty($valueIds)) {
            return;
        }
        $groupIds = OptionValue::whereIn('id', $valueIds)->pluck('option_group_id');
        if ($groupIds->count() !== $groupIds->unique()->count()) {
            abort(422, 'A variant can only have one value per option group.');
        }
    }

    private function storeWebp($file, string $dir, int $width, int $quality): string
    {
        $path = public_path($dir);
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $name = mt_rand(10000000, 99999999).'.webp';
        Image::make($file)->resize($width, null, function ($c) {
            $c->aspectRatio();
            $c->upsize();
        })->encode('webp', $quality)->save($path.$name);

        return '/'.$dir.$name;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && file_exists(public_path($path))) {
            @unlink(public_path($path));
        }
    }
}
