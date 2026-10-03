@extends('admin.pages.master')
@section('title', 'Flash Sales')

@section('content')

    <div class="container-fluid mb-3">
        <a href="{{ route('flash.create') }}" class="btn btn-primary"><i class="ri-add-line me-1"></i> Add New Flash Sale</a>
    </div>

    <div class="container-fluid">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Flash Sales <small class="text-muted">— scheduled prices that beat the shelf price inside their window, then expire on their own</small></h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr><th>#</th><th>Product</th><th>Pack</th><th>Flash price</th><th>Window</th><th>Live</th><th>Active</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($sales as $sale)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $sale->product?->name ?? '—' }}</strong></td>
                                    <td>{{ $sale->variant?->sku ?? 'All packs' }}</td>
                                    <td><span class="badge bg-danger">£{{ number_format($sale->promo_price, 2) }}</span>@if ($sale->offer)<br><small class="text-muted">in {{ $sale->offer->name }}</small>@endif</td>
                                    <td class="text-muted small">{{ $sale->starts_at->format('d M, H:i') }} → {{ $sale->ends_at->format('d M, H:i') }}</td>
                                    <td>{!! $sale->isLive() ? '<span class="badge bg-success">Live</span>' : '<span class="badge bg-secondary">Off</span>' !!}</td>
                                    <td>
                                        <div class="form-check form-switch" dir="ltr">
                                            <input type="checkbox" class="form-check-input toggle-status" data-id="{{ $sale->id }}" {{ $sale->status ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('flash.edit', $sale->id) }}" class="btn btn-soft-secondary btn-sm"><i class="ri-pencil-fill align-bottom me-1 text-muted"></i> Edit</a>
                                        <form method="POST" action="{{ route('flash.permanent', $sale->id) }}" class="d-inline" onsubmit="return confirm('Copy this flash price into the permanent offer price?');">
                                            @csrf
                                            <button class="btn btn-soft-info btn-sm">Make permanent</button>
                                        </form>
                                        <form method="POST" action="{{ route('flash.delete', $sale->id) }}" class="d-inline" onsubmit="return confirm('Delete this flash sale?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-fill align-bottom me-1 text-muted"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">No flash sales yet — schedule one to run a timed deal with a countdown.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $(document).on('change', '.toggle-status', function() {
                $.post('{{ route('flash.toggleStatus') }}', { id: $(this).data('id') }, function(res) {
                    showSuccess('Flash sale updated.');
                });
            });
        });
    </script>
@endsection
