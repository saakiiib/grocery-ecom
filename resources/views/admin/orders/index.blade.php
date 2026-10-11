@extends('admin.pages.master')
@section('title', 'Orders')

@section('content')

    <div class="container-fluid" id="contentContainer">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">All Orders</h4></div>
            <div class="card-body">
                <ul class="nav nav-pills flex-wrap gap-2 mb-3" id="orderStatusTabs">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" data-status="">All <span class="badge bg-secondary">{{ $totalOrders }}</span></button>
                    </li>
                    @foreach ($statuses as $st)
                        <li class="nav-item">
                            <button type="button" class="nav-link" data-status="{{ $st->slug }}">
                                <span class="badge me-1" style="background:{{ $st->color ?? '#1A2E22' }};">{{ $st->name }}</span>
                                <span class="badge bg-secondary">{{ $statusCounts[$st->slug] ?? 0 }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <div class="table-responsive">
                    <table id="orderTable" class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Sl</th>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="orderStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="orderStatusForm" class="modal-content">
                @csrf
                <input type="hidden" name="return_to" value="index">
                <div class="modal-header">
                    <h5 class="modal-title">Change status · <span id="orderStatusNumber"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="orderStatusPick">New status</label>
                        <select name="status" id="orderStatusPick" class="form-control" required>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->slug }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="orderStatusNote">Note <small class="text-muted">(optional — shown in history)</small></label>
                        <input type="text" name="note" id="orderStatusNote" class="form-control" maxlength="255" placeholder="e.g. Packed and ready">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update status</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });

            var orderStatus = '';

            $('#orderTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                order: [[0, 'desc']],
                ajax: {
                    url: '{{ route('orders.index') }}',
                    data: function (d) { d.status = orderStatus; }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'number', name: 'number' },
                    { data: 'customer', name: 'name' },
                    { data: 'items', name: 'items', orderable: false, searchable: false },
                    { data: 'total', name: 'total' },
                    { data: 'payment', name: 'payment_status', orderable: false, searchable: false },
                    { data: 'status', name: 'status_slug' },
                    { data: 'date', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#orderStatusTabs .nav-link').on('click', function () {
                $('#orderStatusTabs .nav-link').removeClass('active');
                $(this).addClass('active');
                orderStatus = $(this).data('status') || '';
                $('#orderTable').DataTable().ajax.reload();
            });

            var statusModal = new bootstrap.Modal(document.getElementById('orderStatusModal'));
            $('#orderTable').on('click', '[data-status-order]', function () {
                var id = $(this).data('status-order');
                $('#orderStatusNumber').text($(this).data('status-number') || ('#' + id));
                $('#orderStatusPick').val($(this).data('status-current') || '');
                $('#orderStatusNote').val('');
                $('#orderStatusForm').attr('action', '{{ url('/admin/orders') }}/' + id + '/status');
                statusModal.show();
            });
        });
    </script>
@endsection
