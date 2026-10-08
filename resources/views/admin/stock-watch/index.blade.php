@extends('admin.pages.master')
@section('title', 'Stock Watch')

@section('content')

    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-header"><h4 class="card-title mb-0">Out of stock <small class="text-muted">— {{ $outOfStock->total() }} packs</small></h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Pack</th>
                                <th>Product</th>
                                <th>Waiting alerts</th>
                                <th>Since</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($outOfStock as $v)
                                <tr>
                                    <td>{{ $v->sku }}<br><small class="text-muted">{{ $v->combinationLabel() }}</small></td>
                                    <td>{{ $v->product?->name }}</td>
                                    <td>{{ $alertCounts->get($v->id, 0) }}</td>
                                    <td>{{ $v->updated_at->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Everything is in stock. Lovely.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $outOfStock->links() }}
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Expiring within 14 days <small class="text-muted">— {{ $expiring->total() }} packs</small></h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Pack</th>
                                <th>Product</th>
                                <th>Best before</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expiring as $v)
                                <tr>
                                    <td>{{ $v->sku }}</td>
                                    <td>{{ $v->product?->name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($v->expires_at)->format('D j M Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted">Nothing expiring soon.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $expiring->links() }}
            </div>
        </div>
    </div>

@endsection
