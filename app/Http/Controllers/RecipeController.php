<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::where('status', true)->orderBy('sort_order')->orderByDesc('id')->paginate(12);

        return spa('frontend.recipes', compact('recipes'));
    }

    public function show(string $slug)
    {
        $recipe = Recipe::with(['ingredients.product', 'ingredients.variant'])
            ->where('slug', $slug)->where('status', true)->firstOrFail();

        return spa('frontend.recipe', compact('recipe'));
    }

    /** One tap: every available ingredient pack joins the bag. */
    public function addAllToBag(int $id): RedirectResponse
    {
        $recipe = Recipe::with(['ingredients.variant.product'])->where('status', true)->findOrFail($id);
        $bag = BagController::bag();
        $added = 0;
        $skipped = [];
        foreach ($recipe->ingredients as $ingredient) {
            $variant = $ingredient->variant_id ? $ingredient->variant : $ingredient->product?->defaultVariant();
            if (! $variant || ! $variant->product || ! $variant->product->status || ! $variant->status || ! $variant->in_stock) {
                $skipped[] = $ingredient->product?->name ?? 'Unavailable item';

                continue;
            }
            $bag[$variant->id] = min(($bag[$variant->id] ?? 0) + $ingredient->qty, 99);
            $added++;
        }
        session()->put(BagController::SESSION_KEY, $bag);

        $message = $added > 0 ? "Added {$added} ingredient".($added === 1 ? '' : 's')." for {$recipe->title}." : "Nothing for {$recipe->title} is available right now.";
        if ($skipped !== []) {
            $message .= ' Skipped: '.implode(', ', $skipped).'.';
        }

        return redirect()->route('bag')->with('bag_notice', $message);
    }
}
