@extends('admin.pages.master')
@section('title', 'Order Statuses')

@section('content')

    <div class="container-fluid">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Order Statuses <small class="text-muted">— the order lifecycle. Every change writes a history row with who and when.</small></h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr><th>#</th><th>Status</th><th>Slug</th><th>Sort</th><th>Final?</th><th>Active</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($statuses as $status)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span class="badge fs-6" style="background:{{ $status->color }};">{{ $status->name }}</span></td>
                                    <td><code>{{ $status->slug }}</code></td>
                                    <td>{{ $status->sort_order }}</td>
                                    <td>{{ $status->is_final ? 'Yes' : 'No' }}</td>
                                    <td>
                                        <div class="form-check form-switch" dir="ltr">
                                            <input type="checkbox" class="form-check-input toggle-status" data-id="{{ $status->id }}" {{ $status->is_active ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td><a href="{{ route('order-statuses.edit', $status->id) }}" class="btn btn-soft-secondary btn-sm"><i class="ri-pencil-fill align-bottom me-1 text-muted"></i> Edit</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-muted mt-2 mb-0"><small>Statuses cannot be deleted — order history depends on them. Deactivate one instead and it disappears from the admin dropdown.</small></p>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $(document).on('change', '.toggle-status', function() {
                $.post('{{ route('order-statuses.toggleStatus') }}', { id: $(this).data('id') }, function(res) {
                    showSuccess('Status updated.');
                });
            });
        });
    </script>
@endsection
