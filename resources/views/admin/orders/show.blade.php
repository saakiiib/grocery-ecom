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
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr><th>Item</th><th>Pack</th><th>Unit</th><th>Qty</th><th class="text-end">Line total</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->items as $item)
                                        <tr>
                                            <td>{{ $item->product_name }}<br><small class="text-muted">{{ $item->variant_sku }}</small></td>
                                            <td>{{ $item->pack_label }}</td>
                                            <td>£{{ number_format($item->unit_price, 2) }}</td>
                                            <td>{{ $item->qty }}</td>
                                            <td class="text-end">£{{ number_format($item->line_total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr><th colspan="4">Subtotal</th><th class="text-end">£{{ number_format($order->subtotal, 2) }}</th></tr>
                                    @if ($order->points_discount > 0)<tr><th colspan="4">Loyalty points ({{ $order->points_redeemed }})</th><th class="text-end">−£{{ number_format($order->points_discount, 2) }}</th></tr>@endif
                                    @if ($order->points_earned > 0)<tr><th colspan="4">Points earned</th><th class="text-end">+{{ $order->points_earned }}</th></tr>@endif
                                    <tr><th colspan="4">Delivery</th><th class="text-end">{{ $order->delivery_fee > 0 ? '£'.number_format($order->delivery_fee, 2) : 'Free' }}</th></tr>
                                    <tr><th colspan="4">Total</th><th class="text-end">£{{ number_format($order->total, 2) }}</th></tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6>Deliver to</h6>
                                <p class="mb-1"><strong>{{ $order->name }}</strong> · {{ $order->phone }}</p>
                                <p class="mb-1">{{ $order->address }}, {{ $order->city }} {{ $order->postcode }}</p>
                                <p class="mb-1">Slot: <strong>{{ $order->delivery_date->format('D j M Y') }}</strong> · {{ $order->delivery_slot_label }}</p>
                                @if ($order->notes)<p class="mb-1 text-muted">Note: {{ $order->notes }}</p>@endif
                                @if ($order->user)<p class="mb-0 text-muted">Account: {{ $order->user->name }} ({{ $order->user->email }})</p>@endif
                            </div>
                            <div class="col-md-6">
                                <h6>Payment</h6>
                                <p class="mb-1">{{ $order->paymentLabel() }} · <span class="badge {{ $order->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($order->payment_status) }}</span></p>
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
                <a href="{{ route('orders.index') }}" class="btn btn-secondary w-100">← Back to orders</a>
            </div>
        </div>
    </div>

@endsection
