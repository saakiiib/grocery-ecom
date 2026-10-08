<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\ShoppingList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShoppingListController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $lists = ShoppingList::with(['items.variant.product'])
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->get();

        return spa('frontend.lists', compact('lists'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:100']);
        ShoppingList::create(['user_id' => auth()->id(), 'name' => $data['name']]);

        return redirect()->route('lists.index')->with('status', 'List created.');
    }

    public function destroy(int $id): RedirectResponse
    {
        ShoppingList::where('id', $id)->where('user_id', auth()->id())->firstOrFail()->delete();

        return redirect()->route('lists.index')->with('status', 'List deleted.');
    }

    public function addItem(Request $request, int $id): RedirectResponse
    {
        $list = ShoppingList::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'qty' => 'nullable|integer|min:1|max:99',
        ]);
        $variant = ProductVariant::with('product')->findOrFail($data['variant_id']);
        if (! $variant->product || ! $variant->product->status || ! $variant->status || ! $variant->in_stock) {
            return redirect()->route('lists.index')->with('status', 'Sorry, that item is out of stock.');
        }
        $list->items()->updateOrCreate(
            ['product_variant_id' => $variant->id],
            ['qty' => $data['qty'] ?? 1]
        );

        return redirect()->route('lists.index')->with('status', $variant->product->name.' saved to '.$list->name.'.');
    }

    public function removeItem(int $listId, int $itemId): RedirectResponse
    {
        $list = ShoppingList::where('id', $listId)->where('user_id', auth()->id())->firstOrFail();
        $list->items()->where('id', $itemId)->delete();

        return redirect()->route('lists.index')->with('status', 'Item removed.');
    }

    public function addAllToBag(int $id): RedirectResponse
    {
        $list = ShoppingList::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
        [$added, $skipped] = $list->addAllToBag();

        $message = $added > 0 ? "Added {$added} item".($added === 1 ? '' : 's')." from {$list->name} to your bag." : "Nothing from {$list->name} is available right now.";
        if ($skipped !== []) {
            $message .= ' Skipped: '.implode(', ', $skipped).'.';
        }

        return redirect()->route('bag')->with('bag_notice', $message);
    }
}
