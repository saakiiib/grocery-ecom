@extends('admin.pages.master')
@section('title', 'Order ' . $order->number)

@section('content')

    <div class="container-fluid">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4 class="card-title mb-0 flex-grow-1">Order {{ $order->number }}</h4>
                        @php $st = $order->status; @endphp
                        <span class="badge fs-6" style="background:{{ $st?->color ?? '#1A2E22' }};">{{ $st?->name ?? ucfirst($order->status_slug) }}</span>
                        <a href="{{ route('orders.invoice', $order->id) }}" class="btn btn-soft-primary btn-sm ms-2">Invoice</a>
                        <a href="{{ route('orders.invoicePdf', $order->id) }}" class="btn btn-soft-secondary btn-sm ms-1">PDF</a>
                    </div>
                    <div class="card-body">
                        @php $editable = ! in_array($order->status_slug, ['delivered', 'cancelled'], true); @endphp
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr><th>Item</th><th>Pack</th><th>Unit</th><th>Qty</th><th class="text-end">Line total</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->items as $item)
                                        <tr @if ($item->status !== 'ok') class="table-light" @endif>
                                            <td>{{ $item->product_name }}<br><small class="text-muted">{{ $item->variant_sku }}</small>@if ($item->promo_label)<br><span class="badge bg-success">{{ $item->promo_label }}{{ $item->free_qty > 0 ? ' · '.$item->free_qty.' free' : '' }}</span>@endif</td>
                                            <td>{{ $item->pack_label }}</td>
                                            @if ($editable)
                                                <td>
                                                    <input type="hidden" form="lines-form" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                                    <input type="number" form="lines-form" name="items[{{ $loop->index }}][unit_price]" class="form-control form-control-sm" style="width:90px;" required step="0.01" min="0" max="999999" value="{{ number_format($item->unit_price, 2, '.', '') }}">
                                                </td>
                                                <td><input type="number" form="lines-form" name="items[{{ $loop->index }}][qty]" class="form-control form-control-sm" style="width:70px;" required min="0" max="99" value="{{ $item->qty }}" title="0 removes the line"></td>
                                            @else
                                                <td>£{{ number_format($item->unit_price, 2) }}</td>
                                                <td>{{ $item->qty }}</td>
                                            @endif
                                            <td class="text-end">£{{ number_format($item->line_total, 2) }}</td>
                                            <td class="text-nowrap">
                                                @if ($item->status === 'ok')
                                                    @if ($editable)
                                                        <form method="POST" action="{{ route('orders.items.delete', [$order->id, $item->id]) }}" class="d-inline" onsubmit="return confirm('Remove {{ $item->product_name }} from this order? Totals recalculate.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-soft-danger btn-sm">Remove</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('orders.items.unavailable', [$order->id, $item->id]) }}" class="d-inline" onsubmit="return confirm('Mark {{ $item->product_name }} unavailable? Its value goes back to the shopper.');">
                                                            @csrf
                                                            <button class="btn btn-soft-warning btn-sm">Unavailable</button>
                                                        </form>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-warning text-dark">Unavailable — refunded</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr><th colspan="5">Subtotal</th><th class="text-end">£{{ number_format($order->subtotal, 2) }}</th></tr>
                                    @if ($order->coupon_discount > 0)<tr><th colspan="5">Coupon {{ $order->coupon_code }}</th><th class="text-end">−£{{ number_format($order->coupon_discount, 2) }}</th></tr>@endif
                                    @if ($order->points_discount > 0)<tr><th colspan="5">Loyalty points ({{ $order->points_redeemed }})</th><th class="text-end">−£{{ number_format($order->points_discount, 2) }}</th></tr>@endif
                                    @if ($order->points_earned > 0)<tr><th colspan="5">Points earned</th><th class="text-end">+{{ $order->points_earned }}</th></tr>@endif
                                    <tr><th colspan="5">Delivery</th><th class="text-end">{{ $order->delivery_fee > 0 ? '£'.number_format($order->delivery_fee, 2) : 'Free' }}</th></tr>
                                    @if ($order->vat_amount > 0)<tr><th colspan="4" class="fw-normal text-muted">Includes VAT @ {{ number_format($order->vat_percent, 2) }}%</th><th class="text-end fw-normal text-muted">£{{ number_format($order->vat_amount, 2) }}</th></tr>@endif
                                    @if ($order->refunded_amount > 0)<tr><th colspan="5">Refunded</th><th class="text-end">−£{{ number_format($order->refunded_amount, 2) }}</th></tr>@endif
                                    <tr><th colspan="5">Total</th><th class="text-end">£{{ number_format($order->total, 2) }}</th></tr>
                                    @if ($order->amount_paid > 0)<tr><th colspan="5" class="fw-normal text-muted">Paid to date</th><th class="text-end fw-normal text-muted">£{{ number_format($order->amount_paid, 2) }}</th></tr>@endif
                                </tfoot>
                            </table>
                        </div>
                        @if ($editable)
                            <form method="POST" action="{{ route('orders.items.save', $order->id) }}" id="lines-form" class="d-none">@csrf</form>
                            <div class="d-flex gap-2 align-items-center mt-2">
                                <button type="submit" form="lines-form" class="btn btn-primary btn-sm">Save all lines</button>
                                <small class="text-muted">Qty 0 removes a line. Unit prices are £ — totals, delivery and VAT recalculate on save.</small>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header"><h6 class="card-title mb-0">Add a pack</h6></div>
                                <div class="card-body">
                                    <form method="POST" action="{{ route('orders.items.store', $order->id) }}" class="row g-2 align-items-end" id="add-item-form">
                                        @csrf
                                        <div class="col-md-5">
                                            <label class="form-label">Product</label>
                                            <select class="form-control" id="add-product" required>
                                                <option value="">Choose…</option>
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Pack</label>
                                            <select name="variant_id" class="form-control" id="add-variant" required>
                                                <option value="">Pick a product first…</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Qty</label>
                                            <input type="number" name="qty" class="form-control" required min="1" max="99" value="1">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="submit" class="btn btn-primary w-100">Add</button>
                                        </div>
                                    </form>
                                    <script>
                                        (function () {
                                            var product = document.getElementById('add-product');
                                            var variant = document.getElementById('add-variant');
                                            if (!product || !variant) return;
                                            product.addEventListener('change', function () {
                                                variant.innerHTML = '<option value="">Loading…</option>';
                                                if (!product.value) { variant.innerHTML = '<option value="">Pick a product first…</option>'; return; }
                                                fetch('/admin/products/' + product.value + '/variants', { headers: { 'Accept': 'application/json' } })
                                                    .then(function (res) { return res.json(); })
                                                    .then(function (rows) {
                                                        variant.innerHTML = rows.map(function (v) {
                                                            var label = (v.combination || v.sku || ('Pack #' + v.id)) + ' · £' + Number(v.mrp).toFixed(2) + (v.in_stock && v.status ? '' : ' (out)');
                                                            return '<option value="' + v.id + '"' + (v.in_stock && v.status ? '' : ' disabled') + '>' + label + '</option>';
                                                        }).join('') || '<option value="">No packs</option>';
                                                    })
                                                    .catch(function () { variant.innerHTML = '<option value="">Could not load</option>'; });
                                            });
                                        })();
                                    </script>
                                </div>
                            </div>
                        @endif

                        <div class="row mt-3">
                            <div class="col-md-4">
                                <h6>Ship to</h6>
                                <p class="mb-1"><strong>{{ $order->name }}</strong> · {{ $order->phone }}</p>
                                <p class="mb-1">{{ $order->address }}, {{ $order->city }} {{ $order->postcode }}</p>
                                <p class="mb-1">Slot: <strong>{{ $order->delivery_date ? $order->delivery_date->format('D j M Y') : '—' }}</strong> · {{ $order->delivery_slot_label }}</p>
                                <p class="mb-1">Fulfilment: <strong>{{ ($order->fulfillment ?? 'delivery') === 'pickup' ? 'Click & Collect' : 'Home delivery' }}</strong>@if ($order->driver_name) · Driver: <strong>{{ $order->driver_name }}</strong>@endif</p>
                                @if ($order->notes)<p class="mb-1 text-muted">Note: {{ $order->notes }}</p>@endif
                                <p class="mb-1">If unavailable: <strong>{{ $order->substitutionLabel() }}</strong></p>
                                @if ($order->user)<p class="mb-0 text-muted">Account: {{ $order->user->name }} ({{ $order->user->email }})</p>@endif
                            </div>
                            @php $billTo = $order->billTo(); @endphp
                            <div class="col-md-4">
                                <h6>Bill to</h6>
                                <p class="mb-1"><strong>{{ $billTo['name'] }}</strong> · {{ $billTo['phone'] }}</p>
                                <p class="mb-0">{{ $billTo['address'] }}, {{ $billTo['city'] }} {{ $billTo['postcode'] }}</p>
                            </div>
                            <div class="col-md-4">
                                <h6>Payment</h6>
                                <p class="mb-1">{{ $order->paymentLabel() }} · <span class="badge {{ $order->isPaid() ? 'bg-success' : 'bg-warning text-dark' }}">{{ $order->paymentStatusLabel() }}</span></p>
                                @if ($order->payment_reference)<p class="mb-0 text-muted"><small>Ref: {{ $order->payment_reference }}</small></p>@endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">Status history</h4></div>
                    <div class="card-body">
                        @foreach ($order->histories as $h)
                            @php $to = $h->toStatus(); $from = $h->fromStatus(); @endphp
                            <div class="d-flex gap-2 mb-2 align-items-baseline">
                                <span class="badge" style="background:{{ $to?->color ?? '#6c757d' }};">{{ $to?->name ?? $h->to_slug }}</span>
                                <span class="text-muted">
                                    @if ($from) from {{ $from->name }} · @endif
                                    {{ $h->created_at->format('d M Y, h:i A') }}
                                    @if ($h->changer) · by {{ $h->changer->name }} @endif
                                    @if ($h->note) — {{ $h->note }} @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">Change status</h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('orders.updateStatus', $order->id) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">New status</label>
                                <select name="status" class="form-control" required>
                                    @foreach ($statuses as $s)
                                        <option value="{{ $s->slug }}" {{ $order->status_slug === $s->slug ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Note <small class="text-muted">(optional — shown in history)</small></label>
                                <input type="text" name="note" class="form-control" maxlength="255" placeholder="e.g. Left with neighbour">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Update status</button>
                        </form>
                        <p class="text-muted mt-2 mb-0"><small>Every change is recorded in the history with who made it and when. Statuses themselves are managed under Settings → Order Statuses.</small></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">Driver</h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('orders.assignDriver', $order->id) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Driver name <small class="text-muted">(shown on tracking)</small></label>
                                <input type="text" name="driver_name" class="form-control" maxlength="100" value="{{ old('driver_name', $order->driver_name) }}" placeholder="e.g. Kamal">
                            </div>
                            <button type="submit" class="btn btn-secondary w-100">Save driver</button>
                        </form>
                    </div>
                </div>
                @php $refundable = $order->refundableAmount(); @endphp
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">Refund</h4></div>
                    <div class="card-body">
                        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                        @if ($order->refunded_amount > 0)<p class="mb-2">Already refunded: <strong>£{{ number_format($order->refunded_amount, 2) }}</strong></p>@endif
                        @if ($refundable > 0)
                            <p class="text-muted">Up to <strong>£{{ number_format($refundable, 2) }}</strong> can go back to the shopper via {{ $order->paymentLabel() }}.</p>
                            <form method="POST" action="{{ route('orders.refund', $order->id) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Amount (£)</label>
                                    <input type="number" name="amount" class="form-control" required step="0.01" min="0.01" max="{{ number_format($refundable, 2, '.', '') }}" value="{{ number_format($refundable, 2, '.', '') }}">
                                    @error('amount')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reason <small class="text-muted">(optional — saved in history)</small></label>
                                    <input type="text" name="reason" class="form-control" maxlength="255" placeholder="e.g. Missing item">
                                </div>
                                <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Refund this amount via {{ $order->paymentLabel() }}?');">Issue refund</button>
                            </form>
                        @else
                            <p class="text-muted mb-0">Nothing to refund — {{ $order->payment_method === 'cod' ? 'cash on delivery takes no online money.' : 'this order is unpaid or fully refunded.' }}</p>
                        @endif
                    </div>
                </div>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary w-100">← Back to orders</a>
            </div>
        </div>
    </div>

@endsection
