@extends('admin.pages.master')
@section('title', 'BOGO Offers')

@section('content')

    <div class="container-fluid mb-3">
        <a href="{{ route('bogo.create') }}" class="btn btn-primary"><i class="ri-add-line me-1"></i> Add New Offer</a>
    </div>

    <div class="container-fluid">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">BOGO Offers <small class="text-muted">— buy X get Y free on the same item, applied automatically in the bag</small></h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr><th>#</th><th>Product</th><th>Pack</th><th>Deal</th><th>Dates</th><th>Active</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($offers as $offer)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $offer->product?->name ?? '—' }}</strong></td>
                                    <td>{{ $offer->variant?->sku ?? 'All packs' }}</td>
                                    <td><span class="badge bg-success">Buy {{ $offer->buy_qty }} Get {{ $offer->free_qty }} FREE</span>@if ($offer->offer)<br><small class="text-muted">in {{ $offer->offer->name }}</small>@endif</td>
                                    <td class="text-muted small">
                                        {{ $offer->starts_at ? $offer->starts_at->format('d M Y') : '—' }} →
                                        {{ $offer->ends_at ? $offer->ends_at->format('d M Y') : '—' }}
                                    </td>
                                    <td>
                                        <div class="form-check form-switch" dir="ltr">
                                            <input type="checkbox" class="form-check-input toggle-status" data-id="{{ $offer->id }}" {{ $offer->status ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('bogo.edit', $offer->id) }}" class="btn btn-soft-secondary btn-sm"><i class="ri-pencil-fill align-bottom me-1 text-muted"></i> Edit</a>
                                        <form method="POST" action="{{ route('bogo.delete', $offer->id) }}" class="d-inline" onsubmit="return confirm('Delete this offer?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-fill align-bottom me-1 text-muted"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No BOGO offers yet — add one to start giving free units automatically.</td></tr>
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
                $.post('{{ route('bogo.toggleStatus') }}', { id: $(this).data('id') }, function(res) {
                    showSuccess('Offer updated.');
                });
            });
        });
    </script>
@endsection
