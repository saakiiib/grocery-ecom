@extends('admin.pages.master')
@section('title', 'Delivery Zones')

@section('content')

    <div class="container-fluid mb-3">
        <a href="{{ route('delivery-zones.create') }}" class="btn btn-primary"><i class="ri-add-line me-1"></i> Add New Zone</a>
    </div>

    <div class="container-fluid">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Delivery Zones <small class="text-muted">— postcode prefixes we deliver to; empty list means everywhere</small></h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr><th>#</th><th>Name</th><th>Postcode prefixes</th><th>Sort</th><th>Active</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($zones as $zone)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $zone->name }}</strong></td>
                                    <td>{{ $zone->postcodes_count > 0 ? $zone->postcodes_count.' prefix'.($zone->postcodes_count === 1 ? '' : 'es') : '—' }}</td>
                                    <td>{{ $zone->sort_order }}</td>
                                    <td>
                                        <div class="form-check form-switch" dir="ltr">
                                            <input type="checkbox" class="form-check-input toggle-status" data-id="{{ $zone->id }}" {{ $zone->is_active ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('delivery-zones.edit', $zone->id) }}" class="btn btn-soft-secondary btn-sm"><i class="ri-pencil-fill align-bottom me-1 text-muted"></i> Edit</a>
                                        <form method="POST" action="{{ route('delivery-zones.delete', $zone->id) }}" class="d-inline" onsubmit="return confirm('Delete this zone?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-fill align-bottom me-1 text-muted"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">No zones yet — while the list is empty we deliver everywhere. Add zones to restrict delivery.</td></tr>
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
                $.post('{{ route('delivery-zones.toggleStatus') }}', { id: $(this).data('id') }, function(res) {
                    showSuccess('Zone updated.');
                });
            });
        });
    </script>
@endsection
