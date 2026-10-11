<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Controller;
use App\Models\CompanyDetails;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Support\Delivery;
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
                ->setRowAttr([
                    'style' => function ($row) {
                        $color = $row->status?->color ?? '#1A2E22';

                        return 'box-shadow: inset 4px 0 0 '.$color.'; background-color: '.$color.'0d;';
                    },
                ])
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

                    return e($row->paymentLabel()).'<br><span class="badge '.$badge.'">'.e($row->paymentStatusLabel()).'</span>';
                })
                ->addColumn('status', function ($row) {
                    $st = $row->status;
                    $color = $st?->color ?? '#1A2E22';
                    $name = $st?->name ?? ucfirst($row->status_slug);

                    return '<span class="badge" style="background:'.$color.';">'.$name.'</span>';
                })
                ->addColumn('date', fn ($row) => $row->created_at->format('d M Y, h:i A').'<br><small class="text-muted">For '.($row->delivery_date ? $row->delivery_date->format('D j M') : '—').'</small>')
                ->addColumn('action', function ($row) {
                    return '<span class="text-nowrap">'
                        .'<a href="'.route('orders.show', $row->id).'" class="btn btn-soft-secondary btn-sm" title="View order"><i class="ri-eye-fill align-bottom text-muted"></i></a> '
                        .'<button type="button" class="btn btn-soft-primary btn-sm" title="Change status" data-status-order="'.$row->id.'" data-status-current="'.e($row->status_slug).'" data-status-number="'.e($row->number).'"><i class="ri-refresh-line align-bottom"></i></button> '
                        .'<a href="'.route('orders.invoicePdf', $row->id).'" class="btn btn-soft-success btn-sm" title="Download invoice"><i class="ri-download-2-line align-bottom"></i></a> '
                        .'<a href="'.route('orders.invoice', $row->id).'?print=1" target="_blank" rel="noopener" class="btn btn-soft-warning btn-sm" title="Print invoice"><i class="ri-printer-line align-bottom"></i></a>'
                        .'</span>';
                })
                ->rawColumns(['number', 'customer', 'payment', 'status', 'date', 'action'])
                ->make(true);
        }

        $statuses = OrderStatus::ordered();
        $statusCounts = Order::selectRaw('status_slug, COUNT(*) as c')->groupBy('status_slug')->pluck('c', 'status_slug')->all();
        $totalOrders = Order::count();

        return view('admin.orders.index', compact('statuses', 'statusCounts', 'totalOrders'));
    }

    public function show(int $id)
    {
        $order = Order::with(['items', 'histories.changer', 'status', 'user', 'payments'])->findOrFail($id);
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
        $pdf = Pdf::loadView('admin.orders.invoice', $this->invoiceData($order));
        if (Setting::get('invoice_format', 'a4') === 'pos') {
            // 80mm thermal roll; tall enough for a weekly shop, extra pages only when truly needed.
            $pdf->setPaper([0, 0, 226.77, 2000]);
        } else {
            $pdf->setPaper('a4');
        }

        return $pdf->download('invoice-'.$order->number.'.pdf');
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
            'return_to' => 'nullable|string|in:index',
        ]);
        // List-page modal stays on the list; details page returns to itself.
        $back = ($data['return_to'] ?? null) === 'index'
            ? redirect()->route('orders.index')
            : redirect()->route('orders.show', $order->id);

        if ($data['status'] === 'cancelled' && $order->refundableAmount() > 0) {
            return $back->with('error', 'This order still has £'.number_format($order->refundableAmount(), 2).' of online money on it — issue the refund first, then cancel.');
        }
        if ($data['status'] !== $order->status_slug && ! $order->canTransitionTo($data['status'])) {
            return $back->with('error', 'An order cannot move from '.$order->status_slug.' to '.$data['status'].'. Final orders never move.');
        }

        $changed = $order->changeStatus($data['status'], auth()->id(), $data['note'] ?? null);

        return $back->with(
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
     * Edit shopper, delivery/billing addresses, fulfilment, day and notes.
     * The fee re-derives from Shop Settings (today vs next working day).
     */
    public function updateDetails(Request $request, int $id): RedirectResponse
    {
        $order = Order::findOrFail($id);
        if (in_array($order->status_slug, ['delivered', 'cancelled'], true)) {
            return redirect()->route('orders.show', $order->id)->with('error', 'That order is already '.$order->status_slug.' — details can no longer change.');
        }
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'postcode' => 'required|string|max:20',
            'billing_name' => 'nullable|string|max:100',
            'billing_phone' => 'nullable|string|max:30',
            'billing_address' => 'nullable|string|max:500',
            'billing_city' => 'nullable|string|max:100',
            'billing_postcode' => 'nullable|string|max:20',
            'fulfillment' => 'required|string|in:delivery,pickup',
            'delivery_date' => 'nullable|date',
            'delivery_speed' => 'nullable|string|in:today,standard',
            'notes' => 'nullable|string|max:1000',
            'substitution_preference' => 'required|string|in:substitute,refund,call',
        ]);

        DB::transaction(function () use ($order, $data) {
            $pickup = $data['fulfillment'] === 'pickup';
            $date = $data['delivery_date'] ?: null;
            if (! $date && ! empty($data['delivery_speed'])) {
                $date = Delivery::dateFor($data['delivery_speed']);
            }
            $slotChanged = ($date ?? '') !== (string) ($order->delivery_date?->format('Y-m-d') ?? '')
                || $data['fulfillment'] !== ($order->fulfillment ?? 'delivery');
            $speed = $date && $date === Delivery::todayDate() ? Delivery::SPEED_TODAY : Delivery::SPEED_STANDARD;
            $order->fill([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'address' => $data['address'],
                'city' => $data['city'],
                'postcode' => $data['postcode'],
                'billing_name' => $data['billing_name'] ?: null,
                'billing_phone' => $data['billing_phone'] ?: null,
                'billing_address' => $data['billing_address'] ?: null,
                'billing_city' => $data['billing_city'] ?: null,
                'billing_postcode' => $data['billing_postcode'] ?: null,
                'fulfillment' => $data['fulfillment'],
                'delivery_date' => $date,
                'delivery_slot_id' => null,
                'delivery_slot_label' => Delivery::labelFor($speed, $pickup),
                'notes' => $data['notes'] ?: null,
                'substitution_preference' => $data['substitution_preference'],
            ]);
            if ($slotChanged) {
                $this->recalculate($order);
            } else {
                $order->save();
            }
            $order->histories()->create([
                'from_slug' => $order->status_slug,
                'to_slug' => $order->status_slug,
                'changed_by' => auth()->id(),
                'note' => mb_substr('Details edited (shopper, address, delivery).', 0, 255),
            ]);
        });

        return redirect()->route('orders.show', $order->id)->with('status', $this->editedMessage($order, 'Details updated.'));
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

        try {
            $result = DB::transaction(function () use ($order, $data) {
                $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ((float) $data['amount'] > $locked->refundableAmount()) {
                    throw new \RuntimeException('Only £'.number_format($locked->refundableAmount(), 2).' is left to refund on this order.');
                }
                $gatewayId = CheckoutController::isStripeMethod($locked->payment_method)
                    ? CheckoutController::stripeRefund($locked, (float) $data['amount'])
                    : CheckoutController::paypalRefund($locked, (float) $data['amount']);

                $refunded = round((float) $locked->refunded_amount + (float) $data['amount'], 2);
                $locked->refunded_amount = $refunded;
                Payment::recordRefund($locked, (float) $data['amount']);
                $locked->payment_status = round($refunded - (float) $locked->total, 2) >= 0 ? 'refunded' : 'partially_refunded';
                $locked->refreshVat();

                $note = 'Refunded £'.number_format($data['amount'], 2).' via '.$locked->paymentLabel().(($data['reason'] ?? null) ? ' — '.$data['reason'] : '').' (ref '.$gatewayId.').';
                $locked->save();
                $locked->histories()->create([
                    'from_slug' => $locked->status_slug,
                    'to_slug' => $locked->status_slug,
                    'changed_by' => auth()->id(),
                    'note' => mb_substr($note, 0, 255),
                ]);

                return $data['amount'];
            });
        } catch (\RuntimeException $e) {
            report($e);

            return redirect()->route('orders.show', $order->id)->with('error', $e->getMessage().' — nothing was recorded.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('orders.show', $order->id)->with('error', 'The payment gateway refused the refund — nothing was recorded. ('.$e->getMessage().')');
        }

        return redirect()->route('orders.show', $order->id)->with('status', '£'.number_format($result, 2).' refunded to the shopper.');
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

        $lineTotal = round((float) $item->line_total, 2);

        try {
            $message = DB::transaction(function () use ($order, $item, $lineTotal) {
                $locked = Order::with('items')->whereKey($order->id)->lockForUpdate()->firstOrFail();
                $line = $locked->items()->findOrFail($item->id);
                if ($line->status !== 'ok') {
                    throw new \RuntimeException('That line is already marked unavailable.');
                }

                $amount = min($lineTotal, $locked->refundableAmount());
                $gatewayId = null;
                if ($amount > 0) {
                    $gatewayId = CheckoutController::isStripeMethod($locked->payment_method)
                        ? CheckoutController::stripeRefund($locked, $amount)
                        : CheckoutController::paypalRefund($locked, $amount);
                    $refunded = round((float) $locked->refunded_amount + $amount, 2);
                    $locked->refunded_amount = $refunded;
                    Payment::recordRefund($locked, $amount);
                    $locked->payment_status = round($refunded - (float) $locked->total, 2) >= 0 ? 'refunded' : 'partially_refunded';
                }

                $locked->subtotal = max(0, round((float) $locked->subtotal - $lineTotal, 2));
                $locked->total = max(0, round((float) $locked->total - $lineTotal, 2));
                $locked->refreshVat();

                $note = $line->qty.' × '.$line->product_name.' unavailable'
                    .($amount > 0 ? ' — £'.number_format($amount, 2).' refunded via '.$locked->paymentLabel().($gatewayId ? ' (ref '.$gatewayId.')' : '') : ' — nothing left to refund')
                    .'.';
                if ($locked->isDirty()) {
                    $locked->save();
                }
                $line->status = 'unavailable';
                $line->save();
                $locked->histories()->create([
                    'from_slug' => $locked->status_slug,
                    'to_slug' => $locked->status_slug,
                    'changed_by' => auth()->id(),
                    'note' => mb_substr($note, 0, 255),
                ]);

                return $line->product_name.' marked unavailable'.($amount > 0 ? ' — £'.number_format($amount, 2).' refunded.' : '.');
            });
        } catch (\RuntimeException $e) {
            report($e);

            return redirect()->route('orders.show', $order->id)->with('error', $e->getMessage().' — nothing was recorded.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('orders.show', $order->id)->with('error', 'The payment gateway refused the line refund — nothing was recorded. ('.$e->getMessage().')');
        }

        return redirect()->route('orders.show', $order->id)->with('status', $message);
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
     * Rebuild money from the lines: subtotal, delivery fee (Shop Settings
     * today/standard rule), total, VAT. Discounts and paid money are never
     * rewritten by edits.
     */
    private function recalculate(Order $order): void
    {
        $subtotal = round($order->items()->sum(DB::raw('qty * unit_price')), 2);
        $fee = (float) $order->delivery_fee;
        if (($order->fulfillment ?? 'delivery') === 'pickup') {
            $fee = 0.0;
        } else {
            $speed = ($order->delivery_date?->format('Y-m-d') ?? '') === Delivery::todayDate()
                ? Delivery::SPEED_TODAY
                : Delivery::SPEED_STANDARD;
            $fee = $subtotal >= Setting::money('delivery_free_over', 50.00) ? 0.0 : Delivery::feeFor($speed, $subtotal);
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
