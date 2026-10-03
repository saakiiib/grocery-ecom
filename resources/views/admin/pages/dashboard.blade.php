@extends('admin.pages.master')
@section('title', 'Dashboard')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-1">Welcome</h4>
                        <p class="text-muted mb-0">Here's a quick overview of your shop.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card"><div class="card-body">
                    <p class="text-muted mb-1">Orders today</p>
                    <h3 class="mb-0">{{ $stats['today_orders'] }}</h3>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body">
                    <p class="text-muted mb-1">Revenue today</p>
                    <h3 class="mb-0">£{{ number_format($stats['today_revenue'], 2) }}</h3>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body">
                    <p class="text-muted mb-1">Open orders</p>
                    <h3 class="mb-0">{{ $stats['open_orders'] }}</h3>
                    <a href="{{ route('orders.index') }}" class="small">View orders →</a>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body">
                    <p class="text-muted mb-1">Registered shoppers</p>
                    <h3 class="mb-0">{{ $stats['customers'] }}</h3>
                </div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4 class="card-title mb-0 flex-grow-1">Latest orders</h4>
                        <a href="{{ route('orders.index') }}" class="btn btn-soft-secondary btn-sm">All orders</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th></tr></thead>
                                <tbody>
                                    @forelse ($recent as $order)
                                        <tr>
                                            <td><a href="{{ route('orders.show', $order->id) }}"><strong>{{ $order->number }}</strong></a></td>
                                            <td>{{ $order->name }}</td>
                                            <td>£{{ number_format($order->total, 2) }}</td>
                                            <td>{{ $order->paymentLabel() }} · {{ $order->paymentStatusLabel() }}</td>
                                            <td><span class="badge" style="background:{{ $order->status?->color ?? '#1A2E22' }};">{{ $order->status?->name ?? ucfirst($order->status_slug) }}</span></td>
                                            <td>{{ $order->created_at->format('d M, h:i A') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted">No orders yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
