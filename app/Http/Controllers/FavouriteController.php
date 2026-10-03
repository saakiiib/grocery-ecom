<?php

namespace App\Http\Controllers;

use App\Models\Favourite;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavouriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Saved items, newest first. */
    public function index()
    {
        $userId = auth()->id();
        $products = Product::with(['category', 'images', 'variants.values.group'])
            ->withReviewSummary()
            ->where('status', true)
            ->whereIn('products.id', Favourite::idsFor($userId))
            ->orderByDesc('favourites.created_at')
            ->join('favourites', 'favourites.product_id', '=', 'products.id')
            ->where('favourites.user_id', $userId)
            ->select('products.*')
            ->get();

        $cards = $products->map(fn ($p) => $this->card($p))->values();

        return spa('frontend.favourites', compact('cards'));
    }

    /** Heart toggle — JSON for the storefront button. */
    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $existing = Favourite::where('user_id', auth()->id())
            ->where('product_id', $data['product_id'])
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['favourited' => false, 'message' => 'Removed from favourites.']);
        }

        Favourite::create(['user_id' => auth()->id(), 'product_id' => $data['product_id']]);

        return response()->json(['favourited' => true, 'message' => 'Saved to favourites.']);
    }

    /** Move every still-available favourite into the session bag. */
    public function moveAll(): RedirectResponse
    {
        $bag = BagController::bag();
        $added = 0;
        $skipped = [];

        $favourites = Favourite::with('product.variants')
            ->where('user_id', auth()->id())
            ->get();

        foreach ($favourites as $fav) {
            $product = $fav->product;
            $variant = $product?->defaultVariant();
            if (! $product || ! $product->status || ! $variant || ! $variant->status || ! $variant->in_stock) {
                $skipped[] = $product?->name ?? 'An item';

                continue;
            }
            $bag[$variant->id] = min(($bag[$variant->id] ?? 0) + 1, 99);
            $added++;
        }

        session()->put(BagController::SESSION_KEY, $bag);

        $message = $added > 0 ? "Moved {$added} favourite".($added === 1 ? '' : 's').' to your bag.' : 'Nothing saved is available right now.';
        if ($skipped !== []) {
            $message .= ' Skipped: '.implode(', ', $skipped).'.';
        }

        return redirect()->route('bag')->with('bag_notice', $message);
    }

    private function card(Product $product): array
    {
        $controller = app(FrontendController::class);

        return $controller->productCard($product);
    }
}
