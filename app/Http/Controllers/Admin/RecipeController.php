<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::withCount('ingredients')->orderBy('sort_order')->orderByDesc('id')->paginate(20);

        return view('admin.recipes.index', compact('recipes'));
    }

    public function create()
    {
        $products = Product::where('status', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.recipes.manage', ['recipe' => new Recipe(['status' => true]), 'products' => $products, 'variants' => collect()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $recipe = Recipe::create($this->validated($request) + [
            'slug' => Str::slug($request->input('title')).'-'.time(),
            'sort_order' => (int) Recipe::max('sort_order') + 1,
        ]);
        $this->syncIngredients($recipe, $request);

        return redirect()->route('admin.recipes.edit', $recipe->id)->with('status', 'Recipe added — now link its ingredients.');
    }

    public function edit(int $id)
    {
        $recipe = Recipe::with('ingredients')->findOrFail($id);
        $products = Product::where('status', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.recipes.manage', compact('recipe', 'products'));
    }

    public function update(Request $request): RedirectResponse
    {
        $recipe = Recipe::findOrFail($request->input('id'));
        $recipe->update($this->validated($request));
        $this->syncIngredients($recipe, $request);

        return redirect()->route('admin.recipes.index')->with('status', 'Recipe saved.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Recipe::findOrFail($id)->delete();

        return redirect()->route('admin.recipes.index')->with('status', 'Recipe deleted.');
    }

    public function toggleStatus(Request $request)
    {
        $recipe = Recipe::findOrFail($request->input('id'));
        $recipe->update(['status' => ! $recipe->status]);

        return response()->json(['success' => true]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'servings' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:255',
        ]);
        $data['status'] = $request->boolean('is_active', true);

        return $data;
    }

    private function syncIngredients(Recipe $recipe, Request $request): void
    {
        $rows = collect($request->input('ingredients', []))
            ->filter(fn ($row) => ! empty($row['product_id']))
            ->values();
        $recipe->ingredients()->delete();
        foreach ($rows as $row) {
            $variant = null;
            if (! empty($row['variant_id'])) {
                $variant = ProductVariant::where('id', $row['variant_id'])
                    ->where('product_id', $row['product_id'])->first();
            }
            $recipe->ingredients()->create([
                'product_id' => $row['product_id'],
                'product_variant_id' => $variant?->id,
                'qty' => max(1, min(99, (int) ($row['qty'] ?? 1))),
            ]);
        }
    }
}
