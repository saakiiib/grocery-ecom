@extends('admin.pages.master')
@section('title', 'Offers')

@section('content')

    <div class="container-fluid mb-3">
        <a href="{{ route('offers.create') }}" class="btn btn-primary"><i class="ri-add-line me-1"></i> Add New Offer</a>
    </div>

    <div class="container-fluid">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Offers <small class="text-muted">— one campaign with dates, BOGO / flash / bundle items tucked underneath</small></h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr><th>#</th><th>Name</th><th>Window</th><th>Items</th><th>Live</th><th>Active</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($offers as $offer)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $offer->name }}</strong></td>
                                    <td class="text-muted small">{{ $offer->starts_at->format('d M, H:i') }} → {{ $offer->ends_at->format('d M, H:i') }}</td>
                                    <td class="text-muted small">{{ $offer->bogos_count }} BOGO · {{ $offer->flashes_count }} flash · {{ $offer->bundles_count }} bundle{{ $offer->bundles_count === 1 ? '' : 's' }}</td>
                                    <td>{!! $offer->isLive() ? '<span class="badge bg-success">Live</span>' : '<span class="badge bg-secondary">Off</span>' !!}</td>
                                    <td>
                                        <div class="form-check form-switch" dir="ltr">
                                            <input type="checkbox" class="form-check-input toggle-status" data-id="{{ $offer->id }}" {{ $offer->status ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('offers.edit', $offer->id) }}" class="btn btn-soft-secondary btn-sm"><i class="ri-pencil-fill align-bottom me-1 text-muted"></i> Manage</a>
                                        <form method="POST" action="{{ route('offers.delete', $offer->id) }}" class="d-inline" onsubmit="return confirm('Delete this offer? Its items become standalone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-fill align-bottom me-1 text-muted"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No offers yet — create one campaign, then attach items to it.</td></tr>
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
                $.post('{{ route('offers.toggleStatus') }}', { id: $(this).data('id') }, function(res) {
                    showSuccess('Offer updated.');
                });
            });
        });
    </script>
@endsection
