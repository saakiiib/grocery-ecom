<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Controller;
use App\Models\CompanyDetails;
use App\Models\DeliverySlot;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $orders = Order::with(['status', 'items'])->orderByDesc('id');
            if ($request->filled('status')) {
                $orders->where('status_slug', $request->status);
            }

            return DataTables::of($orders)
                ->addIndexColumn()
                ->filterColumn('customer', function ($q, $keyword) {
                    $q->where(fn ($w) => $w->where('name', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhere('number', 'like', "%{$keyword}%"));
                })
                ->addColumn('number', fn ($row) => '<a href="'.route('orders.show', $row->id).'"><strong>'.$row->number.'</strong></a>')
                ->addColumn('customer', fn ($row) => e($row->name).'<br><small class="text-muted">'.e($row->phone).'</small>')
                ->addColumn('items', fn ($row) => $row->items->sum('qty').' item'.($row->items->sum('qty') === 1 ? '' : 's'))
                ->addColumn('total', fn ($row) => '£'.number_format($row->total, 2))
                ->addColumn('payment', function ($row) {
                    $badge = $row->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark';

                    return e($row->paymentLabel()).'<br><span class="badge '.$badge.'">'.ucfirst($row->payment_status).'</span>';
                })
                ->addColumn('status', function ($row) {
                    $st = $row->status;
                    $color = $st?->color ?? '#1A2E22';
                    $name = $st?->name ?? ucfirst($row->status_slug);

                    return '<span class="badge" style="background:'.$color.';">'.$name.'</span>';
                })
                ->addColumn('date', fn ($row) => $row->created_at->format('d M Y, h:i A').'<br><small class="text-muted">For '.($row->delivery_date ? $row->delivery_date->format('D j M') : '—').'</small>')
                ->addColumn('action', function ($row) {
                    return '<a href="'.route('orders.show', $row->id).'" class="btn btn-soft-secondary btn-sm"><i class="ri-eye-fill align-bottom me-1 text-muted"></i> View</a>';
                })
                ->rawColumns(['number', 'customer', 'payment', 'status', 'date', 'action'])
                ->make(true);
        }

        $statuses = OrderStatus::ordered();

        return view('admin.orders.index', compact('statuses'));
    }

    public function show(int $id)
    {
        $order = Order::with(['items', 'histories.changer', 'status', 'user'])->findOrFail($id);
        $statuses = OrderStatus::ordered();
        $products = Product::where('status', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.orders.show', compact('order', 'statuses', 'products'));
    }

    /** Printable Tesco-style invoice (browser print). */
    public function invoice(int $id)
    {
        $order = Order::with(['items', 'status', 'user'])->findOrFail($id);

        return view('admin.orders.invoice', $this->invoiceData($order));
    }

    /** Downloadable invoice PDF. */
    public function invoicePdf(int $id)
    {
        $order = Order::with(['items', 'status', 'user'])->findOrFail($id);

        return Pdf::loadView('admin.orders.invoice', $this->invoiceData($order))
            ->setPaper('a4')
            ->download('invoice-'.$order->number.'.pdf');
    }

    /**
     * @return array{order: Order, company: CompanyDetails, logoDataUri: string|null, billTo: array}
     */
    public static function invoiceData(Order $order): array
    {
        $company = CompanyDetails::cached();
        $logoDataUri = null;
        if ($company->company_logo) {
            $path = public_path('uploads/company/'.$company->company_logo);
            if (is_file($path)) {
                $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    default => 'image/png',
                };
                $logoDataUri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
            }
        }

        return [
            'order' => $order,
            'company' => $company,
            'logoDataUri' => $logoDataUri,
            'billTo' => $order->billTo(),
        ];
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|string|exists:order_statuses,slug,is_active,1',
            'note' => 'nullable|string|max:255',
        ]);

        if ($data['status'] === 'cancelled' && $order->refundableAmount() > 0) {
            return redirect()->route('orders.show', $order->id)->with('error', 'This order still has £'.number_format($order->refundableAmount(), 2).' of online money on it — issue the refund first, then cancel.');
        }
        if ($data['status'] !== $order->status_slug && ! $order->canTransitionTo($data['status'])) {
            return redirect()->route('orders.show', $order->id)->with('error', 'An order cannot move from '.$order->status_slug.' to '.$data['status'].'. Final orders never move.');
        }

        $changed = $order->changeStatus($data['status'], auth()->id(), $data['note'] ?? null);

        return redirect()->route('orders.show', $order->id)->with(
            'status',
            $changed ? 'Order moved to '.$changed->toStatus()->name.'.' : 'Order is already '.($order->status?->name ?? $order->status_slug).'.'
        );
    }

    /** Assign (or clear) the delivery driver shown to the shopper on tracking. */
    public function assignDriver(Request $request, int $id): RedirectResponse
    {
        $order = Order::findOrFail($id);
        $data = $request->validate(['driver_name' => 'nullable|string|max:100']);
        $order->update(['driver_name' => $data['driver_name'] ?: null]);

        return redirect()->route('orders.show', $order->id)->with('status', 'Driver updated.');
    }

    /**
     * Partial or full online refund. Gateway first, ledger second —
     * a refused gateway leaves the order untouched.
     */
    public function refund(Request $request, int $id): RedirectResponse
    {
        $order = Order::findOrFail($id);
        $max = $order->refundableAmount();

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$max,
            'reason' => 'nullable|string|max:255',
        ], [
            'amount.max' => 'Only £'.number_format($max, 2).' is left to refund on this order.',
        ]);

        if ($max <= 0) {
            return redirect()->route('orders.show', $order->id)->with('error', 'There is nothing to refund on this order.');
        }

        try {
            $gatewayId = $order->payment_method === 'stripe'
                ? CheckoutController::stripeRefund($order, (float) $data['amount'])
                : CheckoutController::paypalRefund($order, (float) $data['amount']);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('orders.show', $order->id)->with('error', 'The payment gateway refused the refund — nothing was recorded. ('.$e->getMessage().')');
        }

        $refunded = round((float) $order->refunded_amount + (float) $data['amount'], 2);
        $order->refunded_amount = $refunded;
        $order->payment_status = round($refunded - (float) $order->total, 2) >= 0 ? 'refunded' : 'partially_refunded';
        $order->refreshVat();

        $note = 'Refunded £'.number_format($data['amount'], 2).' via '.$order->paymentLabel().(($data['reason'] ?? null) ? ' — '.$data['reason'] : '').' (ref '.$gatewayId.').';
        DB::transaction(function () use ($order, $note) {
            $order->save();
            $order->histories()->create([
                'from_slug' => $order->status_slug,
                'to_slug' => $order->status_slug,
                'changed_by' => auth()->id(),
                'note' => mb_substr($note, 0, 255),
            ]);
        });

        return redirect()->route('orders.show', $order->id)->with('status', '£'.number_format($data['amount'], 2).' refunded to the shopper.');
    }

    /**
     * Packing flow: an item is out of stock. It is marked unavailable and its
     * line value goes back to the shopper (gateway when money moved online).
     */
    public function markUnavailable(Request $request, int $orderId, int $itemId): RedirectResponse
    {
        $order = Order::with('items')->findOrFail($orderId);
        $item = $order->items()->findOrFail($itemId);

        if (in_array($order->status_slug, ['delivered', 'cancelled'], true)) {
            return redirect()->route('orders.show', $order->id)->with('error', 'That order is already '.$order->status_slug.' — lines can no longer change.');
        }
        if ($item->status !== 'ok') {
            return redirect()->route('orders.show', $order->id)->with('error', 'That line is already marked unavailable.');
        }

        $amount = min((float) $item->line_total, $order->refundableAmount());
        $gatewayId = null;
        if ($amount > 0) {
            try {
                $gatewayId = $order->payment_method === 'stripe'
                    ? CheckoutController::stripeRefund($order, $amount)
                    : CheckoutController::paypalRefund($order, $amount);
            } catch (\Throwable $e) {
                report($e);

                return redirect()->route('orders.show', $order->id)->with('error', 'The payment gateway refused the line refund — nothing was recorded. ('.$e->getMessage().')');
            }
            $refunded = round((float) $order->refunded_amount + $amount, 2);
            $order->refunded_amount = $refunded;
            $order->payment_status = round($refunded - (float) $order->total, 2) >= 0 ? 'refunded' : 'partially_refunded';
            $order->refreshVat();
        }

        $note = $item->qty.' × '.$item->product_name.' unavailable'
            .($amount > 0 ? ' — £'.number_format($amount, 2).' refunded via '.$order->paymentLabel().($gatewayId ? ' (ref '.$gatewayId.')' : '') : ' — nothing left to refund')
            .'.';
        DB::transaction(function () use ($order, $item, $note) {
            if ($order->isDirty()) {
                $order->save();
            }
            $item->status = 'unavailable';
            $item->save();
            $order->histories()->create([
                'from_slug' => $order->status_slug,
                'to_slug' => $order->status_slug,
                'changed_by' => auth()->id(),
                'note' => mb_substr($note, 0, 255),
            ]);
        });

        return redirect()->route('orders.show', $order->id)->with('status', $item->product_name.' marked unavailable'.($amount > 0 ? ' — £'.number_format($amount, 2).' refunded.' : '.'));
    }

    /** Add a catalogue pack to the order at the current shelf price. */
    public function addItem(Request $request, int $orderId): RedirectResponse
    {
        $order = Order::with('items')->findOrFail($orderId);
        if (in_array($order->status_slug, ['delivered', 'cancelled'], true)) {
            return redirect()->route('orders.show', $order->id)->with('error', 'That order is already '.$order->status_slug.' — lines can no longer change.');
        }
        $data = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'qty' => 'required|integer|min:1|max:99',
        ]);
        $variant = ProductVariant::with('product')->findOrFail($data['variant_id']);
        if (! $variant->product || ! $variant->product->status || ! $variant->status || ! $variant->in_stock) {
            return redirect()->route('orders.show', $order->id)->with('error', 'That pack is not available right now.');
        }
        $price = (float) $variant->sellingPrice();
        $hit = FlashSale::priceFor($variant->id, $variant->product_id);
        if ($hit && $hit['price'] < $price) {
            $price = $hit['price'];
        }

        DB::transaction(function () use ($order, $variant, $data, $price) {
            $order->items()->create([
                'product_variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name,
                'variant_sku' => $variant->sku,
                'pack_label' => $variant->combinationLabel(),
                'unit_price' => $price,
                'qty' => $data['qty'],
                'line_total' => round($price * $data['qty'], 2),
            ]);
            $this->recalculate($order);
            $order->histories()->create([
                'from_slug' => $order->status_slug,
                'to_slug' => $order->status_slug,
                'changed_by' => auth()->id(),
                'note' => mb_substr('Added '.$data['qty'].' × '.$variant->product->name.' @ £'.number_format($price, 2).'.', 0, 255),
            ]);
        });

        return redirect()->route('orders.show', $order->id)->with('status', $this->editedMessage($order, 'Item added.'));
    }

    /**
     * Save every line at once (qty and/or unit price; qty 0 removes the line).
     * One form wraps the whole items table; one history entry summarises.
     */
    public function updateItems(Request $request, int $orderId): RedirectResponse
    {
        $order = Order::with('items')->findOrFail($orderId);
        if (in_array($order->status_slug, ['delivered', 'cancelled'], true)) {
            return redirect()->route('orders.show', $order->id)->with('error', 'That order is already '.$order->status_slug.' — lines can no longer change.');
        }
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:0|max:99',
            'items.*.unit_price' => 'required|numeric|min:0|max:999999',
        ]);

        $lines = $order->items()->get()->keyBy('id');
        $changes = [];
        foreach ($data['items'] as $row) {
            $item = $lines->get($row['id']);
            if (! $item) {
                continue;
            }
            if ((int) $row['qty'] === (int) $item->qty && (float) $row['unit_price'] === (float) $item->unit_price) {
                continue;
            }
            if ((int) $row['qty'] > (int) $item->qty) {
                $variant = $item->product_variant_id ? ProductVariant::with('product')->find($item->product_variant_id) : null;
                if (! $variant || ! $variant->product || ! $variant->product->status || ! $variant->status || ! $variant->in_stock) {
                    return redirect()->route('orders.show', $order->id)->with('error', $item->product_name.' is no longer available — its quantity cannot go up.');
                }
            }
            $changes[] = [$item, (int) $row['qty'], round((float) $row['unit_price'], 2)];
        }
        if ($changes === []) {
            return redirect()->route('orders.show', $order->id)->with('status', 'No changes to save.');
        }

        DB::transaction(function () use ($order, $changes) {
            $notes = [];
            foreach ($changes as [$item, $qty, $price]) {
                if ($qty === 0) {
                    $notes[] = 'Removed '.$item->qty.' × '.$item->product_name;
                    $item->delete();
                } else {
                    $item->qty = $qty;
                    $item->unit_price = $price;
                    $item->line_total = round($qty * $price, 2);
                    $item->save();
                    $notes[] = $item->product_name.' → '.$qty.' × £'.number_format($price, 2);
                }
            }
            $this->recalculate($order);
            $order->histories()->create([
                'from_slug' => $order->status_slug,
                'to_slug' => $order->status_slug,
                'changed_by' => auth()->id(),
                'note' => mb_substr('Lines edited: '.implode('; ', $notes).'.', 0, 255),
            ]);
        });

        return redirect()->route('orders.show', $order->id)->with('status', $this->editedMessage($order, 'Order updated.'));
    }

    public function removeItem(int $orderId, int $itemId): RedirectResponse
    {
        $order = Order::with('items')->findOrFail($orderId);
        $item = $order->items()->findOrFail($itemId);
        if (in_array($order->status_slug, ['delivered', 'cancelled'], true)) {
            return redirect()->route('orders.show', $order->id)->with('error', 'That order is already '.$order->status_slug.' — lines can no longer change.');
        }

        DB::transaction(function () use ($order, $item) {
            $note = 'Removed '.$item->qty.' × '.$item->product_name.'.';
            $item->delete();
            $this->recalculate($order);
            $order->histories()->create([
                'from_slug' => $order->status_slug,
                'to_slug' => $order->status_slug,
                'changed_by' => auth()->id(),
                'note' => mb_substr($note, 0, 255),
            ]);
        });

        return redirect()->route('orders.show', $order->id)->with('status', $this->editedMessage($order, 'Item removed.'));
    }

    /**
     * Rebuild money from the lines: subtotal, delivery fee (free-over rule),
     * total, VAT. Discounts and paid money are never rewritten by edits.
     */
    private function recalculate(Order $order): void
    {
        $subtotal = round($order->items()->sum(DB::raw('qty * unit_price')), 2);
        $fee = (float) $order->delivery_fee;
        if (($order->fulfillment ?? 'delivery') === 'pickup') {
            $fee = 0.0;
        } else {
            $slot = $order->delivery_slot_id ? DeliverySlot::find($order->delivery_slot_id) : null;
            if ($slot) {
                $fee = $subtotal >= Setting::money('delivery_free_over', 50.00) ? 0.0 : (float) $slot->fee;
            }
        }
        $order->subtotal = $subtotal;
        $order->delivery_fee = round($fee, 2);
        $order->total = max(0, round($subtotal + $fee - (float) $order->points_discount - (float) $order->coupon_discount, 2));
        $order->refreshVat();
        $order->save();
    }

    /** Totals line plus a money warning when a paid order no longer balances. */
    private function editedMessage(Order $order, string $prefix): string
    {
        $message = $prefix.' New total £'.number_format($order->total, 2).'.';
        $due = $order->balanceDue();
        if ($order->isPaid() && $due > 0) {
            $message .= ' Still £'.number_format($due, 2).' short of what was paid — collect it before dispatch.';
        } elseif ($order->isPaid() && $order->refundableAmount() > 0 && $order->total < (float) $order->amount_paid) {
            $message .= ' £'.number_format((float) $order->amount_paid - (float) $order->refunded_amount - (float) $order->total, 2).' can go back via Refund below.';
        }

        return $message;
    }
}
