@extends('admin.pages.master')
@section('title', 'Stock Alerts')

@section('content')

    <div class="container-fluid">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Stock &amp; Price Alerts <small class="text-muted">— {{ $alerts->total() }} total</small></h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Email</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th>Watched at</th>
                                <th>Sent</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($alerts as $a)
                                <tr>
                                    <td>{{ $a->id }}</td>
                                    <td>{{ $a->email }}</td>
                                    <td>{{ $a->product?->name }} @if ($a->variant)<small class="text-muted">({{ $a->variant->sku }})</small>@endif</td>
                                    <td>{{ $a->type === 'price_drop' ? 'Price drop' : 'Back in stock' }}</td>
                                    <td>@if ($a->target_price)£{{ number_format($a->target_price, 2) }}@else—@endif</td>
                                    <td>{{ $a->is_sent ? 'Yes' : 'Waiting' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('stock-alerts.delete', $a->id) }}" onsubmit="return confirm('Delete this alert?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-soft-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No alerts yet — shoppers create them from out-of-stock products.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $alerts->links() }}
            </div>
        </div>
    </div>

@endsection
