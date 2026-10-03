@extends('admin.pages.master')
@section('title', 'Bundles')

@section('content')

    <div class="container-fluid mb-3">
        <a href="{{ route('bundles.create') }}" class="btn btn-primary"><i class="ri-add-line me-1"></i> Add New Bundle</a>
    </div>

    <div class="container-fluid">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Bundles <small class="text-muted">— mix & match pools, cheapest lines group first, bag re-forms itself</small></h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr><th>#</th><th>Name</th><th>Deal</th><th>Pool</th><th>Dates</th><th>Active</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($bundles as $bundle)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $bundle->name }}</strong></td>
                                    <td><span class="badge bg-primary">Any {{ $bundle->required_qty }} for £{{ number_format($bundle->bundle_price, 2) }}</span>@if ($bundle->offer)<br><small class="text-muted">in {{ $bundle->offer->name }}</small>@endif</td>
                                    <td class="text-muted small">{{ $bundle->categories_count }} categor{{ $bundle->categories_count === 1 ? 'y' : 'ies' }} · {{ $bundle->variants_count }} packs</td>
                                    <td class="text-muted small">
                                        {{ $bundle->starts_at ? $bundle->starts_at->format('d M Y') : '—' }} →
                                        {{ $bundle->ends_at ? $bundle->ends_at->format('d M Y') : '—' }}
                                    </td>
                                    <td>
                                        <div class="form-check form-switch" dir="ltr">
                                            <input type="checkbox" class="form-check-input toggle-status" data-id="{{ $bundle->id }}" {{ $bundle->status ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('bundles.edit', $bundle->id) }}" class="btn btn-soft-secondary btn-sm"><i class="ri-pencil-fill align-bottom me-1 text-muted"></i> Edit</a>
                                        <form method="POST" action="{{ route('bundles.delete', $bundle->id) }}" class="d-inline" onsubmit="return confirm('Delete this bundle?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-fill align-bottom me-1 text-muted"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No bundles yet — add one to sell mix-and-match deals.</td></tr>
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
                $.post('{{ route('bundles.toggleStatus') }}', { id: $(this).data('id') }, function(res) {
                    showSuccess('Bundle updated.');
                });
            });
        });
    </script>
@endsection
