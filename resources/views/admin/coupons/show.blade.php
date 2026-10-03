@extends('admin.pages.master')
@section('title', 'Coupon usage')
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h4 class="card-title mb-0 flex-grow-1">Coupon {{ $coupon->code }} — who used it</h4>
            <a href="{{ route('coupons.index') }}" class="btn btn-soft-secondary btn-sm">Back to coupons</a>
        </div>
        <div class="card-body">
            <p class="text-muted">{{ $coupon->orders_count }} use{{ $coupon->orders_count === 1 ? '' : 's' }}{{ $coupon->max_uses !== null ? ' of '.$coupon->max_uses.' allowed' : '' }} · {{ $coupon->max_per_user }} per person · {{ $coupon->expires_at ? 'expires '.$coupon->expires_at->format('d M Y') : 'no expiry' }}</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped w-100">
                    <thead><tr><th>#</th><th>Order</th><th>Shopper</th><th>Discount</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse ($orders as $i => $order)
                            <tr>
                                <td>{{ $orders->firstItem() + $i }}</td>
                                <td><a href="{{ route('orders.show', $order->id) }}">{{ $order->number }}</a></td>
                                <td>{{ $order->user?->name ?? $order->name }}<br><small class="text-muted">{{ $order->user?->email ?? $order->email ?? '' }}</small></td>
                                <td>£{{ number_format($order->coupon_discount, 2) }}</td>
                                <td>{{ $order->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Nobody has used this coupon yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $orders->links() }}</div>
        </div>
    </div>
</div>
@endsection
