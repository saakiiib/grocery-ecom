<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BagController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontendController;
use App\Models\Address;
use App\Models\BogoOffer;
use App\Models\BundleOffer;
use App\Models\Favourite;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UserPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** JSON twin of Account/Address/Favourite controllers (token auth instead of session). */
class AccountApiController extends Controller
{
    public function orders(): JsonResponse
    {
        $user = auth()->user();
        $user->ensureAddressBook();
        $orders = Order::with(['items', 'status'])->where('user_id', $user->id)->orderByDesc('id')->paginate(10);

        return response()->json([
            'orders' => collect($orders->items())->map(fn ($o) => OrderPayload::make($o))->values(),
            'page' => $orders->currentPage(),
            'has_more' => $orders->hasMorePages(),
            'points_balance' => UserPoint::balance($user->id),
            'points_history' => UserPoint::with('order')->where('user_id', $user->id)->orderByDesc('id')->take(10)->get()
                ->map(fn ($p) => [
                    'points' => $p->points,
                    'type' => $p->type,
                    'description' => $p->description,
                    'order_number' => $p->order?->number,
                    'date' => $p->created_at?->format('j M Y'),
                ])->values(),
        ]);
    }

    public function order(string $number): JsonResponse
    {
        $order = Order::with(['items', 'histories', 'status'])
            ->where('number', $number)->where('user_id', auth()->id())->firstOrFail();

        return response()->json([
            'order' => OrderPayload::make($order),
            'stripe_publishable' => CheckoutController::stripePublishable(),
            'paypal_client_id' => CheckoutController::paypalClientId(),
            'paypal_mode' => CheckoutController::paypalMode(),
        ]);
    }

    /** Buy again: put every still-available line back in the bag. */
    public function reorder(string $number): JsonResponse
    {
        $order = Order::with('items')->where('number', $number)->where('user_id', auth()->id())->firstOrFail();

        $bag = BagController::bag();
        $added = 0;
        $skipped = [];
        foreach ($order->items as $item) {
            $variant = $item->product_variant_id ? ProductVariant::with('product')->find($item->product_variant_id) : null;
            if (! $variant || ! $variant->product || ! $variant->product->status || ! $variant->status || ! $variant->in_stock) {
                $skipped[] = $item->product_name;

                continue;
            }
            $bag[$variant->id] = min(($bag[$variant->id] ?? 0) + $item->qty, 99);
            $added++;
        }
        session()->put(BagController::SESSION_KEY, $bag);

        $message = $added > 0 ? "Added {$added} item".($added === 1 ? '' : 's').' from '.$order->number.' to your bag.' : 'Nothing from '.$order->number.' is available right now.';
        if ($skipped !== []) {
            $message .= ' Skipped: '.implode(', ', $skipped).'.';
        }

        return response()->json(array_merge(['message' => $message, 'added' => $added], BagController::detailed()));
    }

    public function pay(string $number): JsonResponse
    {
        return app(AccountController::class)->pay($number);
    }

    public function cancel(string $number): JsonResponse
    {
        $order = Order::where('number', $number)->where('user_id', auth()->id())->firstOrFail();

        if (! in_array($order->status_slug, ['new', 'confirmed'], true)) {
            return response()->json(['message' => 'That order is already being prepared — please contact us to change it.'], 422);
        }
        if ($order->isPaid()) {
            return response()->json(['message' => 'That order is already paid — please contact us and we will refund you.'], 422);
        }

        $order->changeStatus('cancelled', auth()->id(), 'Cancelled by the shopper.');

        return response()->json(['ok' => true, 'message' => $order->number.' was cancelled.']);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = auth()->user();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30|unique:users,phone,'.$user->id,
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
        ]);
        $user->update($data);

        return response()->json([
            'message' => 'Your details were saved.',
            'user' => array_merge($user->fresh()->only(['id', 'name', 'email', 'phone', 'address', 'city', 'postcode']), ['points' => UserPoint::balance($user->id)]),
        ]);
    }

    public function password(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|digits:6|confirmed',
        ], [
            'current_password.current_password' => 'Your current password is not correct',
            'password.confirmed' => 'The new passwords do not match',
        ]);

        auth()->user()->update(['password' => Hash::make($request->input('password'))]);

        return response()->json(['message' => 'Your password was changed.']);
    }

    /* ---------------- Addresses ---------------- */

    public function addresses(): JsonResponse
    {
        auth()->user()->ensureAddressBook();

        return response()->json(['addresses' => auth()->user()->addresses()->get()]);
    }

    public function addressStore(Request $request): JsonResponse
    {
        $address = auth()->user()->addresses()->create($this->addressData($request));
        $this->applyDefaults($address, $request->boolean('is_default_delivery'), $request->boolean('is_default_billing'), true);

        return response()->json(['message' => 'Address saved to your book.', 'addresses' => auth()->user()->addresses()->get()]);
    }

    public function addressUpdate(Request $request, int $id): JsonResponse
    {
        $address = Address::where('user_id', auth()->id())->findOrFail($id);
        $address->update($this->addressData($request));
        $this->applyDefaults($address, $request->boolean('is_default_delivery'), $request->boolean('is_default_billing'), false);

        return response()->json(['message' => 'Address updated.', 'addresses' => auth()->user()->addresses()->get()]);
    }

    public function addressDefault(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['type' => 'required|in:delivery,billing']);
        $address = Address::where('user_id', auth()->id())->findOrFail($id);

        auth()->user()->addresses()->update(['is_default_'.$data['type'] => false]);
        $address->update(['is_default_'.$data['type'] => true]);

        return response()->json(['message' => 'Default address updated.', 'addresses' => auth()->user()->addresses()->get()]);
    }

    public function addressDestroy(int $id): JsonResponse
    {
        $address = Address::where('user_id', auth()->id())->findOrFail($id);
        $wasDelivery = $address->is_default_delivery;
        $wasBilling = $address->is_default_billing;
        $address->delete();

        $next = auth()->user()->addresses()->oldest()->first();
        if ($next) {
            if ($wasDelivery) {
                $next->update(['is_default_delivery' => true]);
            }
            if ($wasBilling) {
                $next->update(['is_default_billing' => true]);
            }
        }

        return response()->json(['message' => 'Address removed.', 'addresses' => auth()->user()->addresses()->get()]);
    }

    private function addressData(Request $request): array
    {
        return $request->validate([
            'label' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'postcode' => 'required|string|max:20',
        ]);
    }

    private function applyDefaults(Address $address, bool $delivery, bool $billing, bool $isNew): void
    {
        $user = auth()->user();
        if ($isNew && $user->addresses()->count() === 1) {
            $delivery = true;
            $billing = true;
        }
        if ($delivery) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default_delivery' => false]);
        }
        if ($billing) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default_billing' => false]);
        }
        $address->update(['is_default_delivery' => $delivery, 'is_default_billing' => $billing]);
    }

    /* ---------------- Favourites ---------------- */

    public function favourites(): JsonResponse
    {
        $userId = auth()->id();
        $products = Product::with(['category', 'images', 'variants.values.group'])
            ->withReviewSummary()->where('status', true)
            ->whereIn('products.id', Favourite::idsFor($userId))
            ->orderByDesc('favourites.created_at')
            ->join('favourites', 'favourites.product_id', '=', 'products.id')
            ->where('favourites.user_id', $userId)
            ->select('products.*')->get();

        $maps = [BundleOffer::coverMap(), FlashSale::liveMap(), BogoOffer::liveAll()];
        $front = app(FrontendController::class);

        return response()->json(['products' => $products->map(fn ($p) => $front->productCard($p, ...$maps))->values()]);
    }

    public function favouritesMoveAll(): JsonResponse
    {
        $bag = BagController::bag();
        $added = 0;
        $skipped = [];
        foreach (Favourite::with('product.variants')->where('user_id', auth()->id())->get() as $fav) {
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

        return response()->json(array_merge(['message' => $message], BagController::detailed()));
    }
}
