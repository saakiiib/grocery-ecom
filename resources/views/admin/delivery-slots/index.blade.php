@extends('admin.pages.master')
@section('title', 'Delivery Slots')

@section('content')

    <div class="container-fluid mb-3">
        <a href="{{ route('delivery-slots.create') }}" class="btn btn-primary"><i class="ri-add-line me-1"></i> Add New Slot</a>
    </div>

    <div class="container-fluid">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Delivery Slots <small class="text-muted">— shoppers pick one at checkout, each with its own fee</small></h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr><th>#</th><th>Name</th><th>Window</th><th>Fee</th><th>Same-day cutoff</th><th>Sort</th><th>Active</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($slots as $slot)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $slot->name }}</strong></td>
                                    <td>{{ $slot->starts_at }} – {{ $slot->ends_at }}</td>
                                    <td>{{ $slot->fee > 0 ? '£'.number_format($slot->fee, 2) : 'Free' }}</td>
                                    <td>{{ $slot->cutoff_hour }}:00</td>
                                    <td>{{ $slot->sort_order }}</td>
                                    <td>
                                        <div class="form-check form-switch" dir="ltr">
                                            <input type="checkbox" class="form-check-input toggle-status" data-id="{{ $slot->id }}" {{ $slot->is_active ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('delivery-slots.edit', $slot->id) }}" class="btn btn-soft-secondary btn-sm"><i class="ri-pencil-fill align-bottom me-1 text-muted"></i> Edit</a>
                                        <form method="POST" action="{{ route('delivery-slots.delete', $slot->id) }}" class="d-inline" onsubmit="return confirm('Delete this slot? Slots with orders cannot be deleted.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-soft-danger btn-sm"><i class="ri-delete-bin-fill align-bottom me-1 text-muted"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
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
                $.post('{{ route('delivery-slots.toggleStatus') }}', { id: $(this).data('id') }, function(res) {
                    showSuccess('Slot updated.');
                });
            });
        });
    </script>
@endsection
