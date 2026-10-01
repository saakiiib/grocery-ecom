<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\UserPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();
        $orders = Order::with(['items', 'status'])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(10);
        $pointsBalance = UserPoint::balance($user->id);
        $pointsHistory = UserPoint::with('order')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->take(10)
            ->get();

        return spa('frontend.account', compact('user', 'orders', 'pointsBalance', 'pointsHistory'));
    }

    public function show(string $number)
    {
        $order = Order::with(['items', 'histories', 'status'])
            ->where('number', $number)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $payPublishable = CheckoutController::stripePublishable();
        $payPaypalClient = CheckoutController::paypalClientId();

        return spa('frontend.order-detail', ['order' => $order, 'user' => auth()->user(), 'payPublishable' => $payPublishable, 'payPaypalClient' => $payPaypalClient]);
    }

    /** Buy again: put every still-available line back in the session bag. */
    public function reorder(string $number): RedirectResponse
    {
        $order = Order::with('items')
            ->where('number', $number)
            ->where('user_id', auth()->id())
            ->firstOrFail();

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

        return redirect()->route('bag')->with('bag_notice', $message);
    }

    public function profile(Request $request): RedirectResponse
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

        return redirect()->to(route('account').'#details')->with('status', 'Your details were saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.current_password' => 'Your current password is not correct',
            'password.confirmed' => 'The new passwords do not match',
        ]);

        auth()->user()->update(['password' => Hash::make($data['password'])]);

        return redirect()->to(route('account').'#password')->with('status', 'Your password was changed.');
    }

    /** Start (or retry) online payment for an unpaid order. Returns SDK credentials. */
    public function pay(string $number): JsonResponse
    {
        $order = $this->ownOrder($number);

        if ($order->isPaid() || in_array($order->status_slug, ['cancelled', 'delivered'], true)) {
            return response()->json(['message' => 'That order does not need payment.'], 422);
        }
        if ($order->payment_method === 'cod') {
            return response()->json(['message' => 'That order is cash on delivery — nothing to pay online.'], 422);
        }

        try {
            if ($order->payment_method === 'stripe') {
                if (! CheckoutController::stripeConfigured()) {
                    return response()->json(['message' => 'Card payment is not available right now.'], 422);
                }
                $intent = CheckoutController::stripeIntent($order);
                $order->payment_reference = $intent['id'];
                $order->save();

                return response()->json([
                    'ok' => true,
                    'stripe' => true,
                    'publishable' => CheckoutController::stripePublishable(),
                    'client_secret' => $intent['client_secret'],
                    'order_number' => $order->number,
                ]);
            }

            if (! CheckoutController::paypalConfigured()) {
                return response()->json(['message' => 'PayPal is not available right now.'], 422);
            }
            $ppOrderId = CheckoutController::paypalCreateOrder($order);
            $order->payment_reference = $ppOrderId;
            $order->save();

            return response()->json([
                'ok' => true,
                'paypal' => true,
                'paypal_order_id' => $ppOrderId,
                'order_number' => $order->number,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Payment could not be started — please try again.'], 422);
        }
    }

    /** Shoppers can cancel their own order while it is untouched or COD. Paid online orders need the shop (refunds). */
    public function cancel(string $number): RedirectResponse
    {
        $order = $this->ownOrder($number);

        if (! in_array($order->status_slug, ['new', 'confirmed'], true)) {
            return redirect()->route('account.order', $order->number)->with('status', 'That order is already being prepared — please contact us to change it.');
        }
        if ($order->isPaid()) {
            return redirect()->route('account.order', $order->number)->with('status', 'That order is already paid — please contact us and we will refund you.');
        }

        $order->changeStatus('cancelled', auth()->id(), 'Cancelled by the shopper.');

        return redirect()->route('account')->with('status', $order->number.' was cancelled.');
    }

    private function ownOrder(string $number): Order
    {
        return Order::where('number', $number)->where('user_id', auth()->id())->firstOrFail();
    }
}
