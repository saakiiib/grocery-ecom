@extends('admin.pages.master')
@section('title', 'Orders')

@section('content')

    <div class="container-fluid" id="contentContainer">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">All Orders</h4></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select id="filterStatus" class="form-select">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $st)
                                <option value="{{ $st->slug }}">{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
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

@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });

            $('#orderTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                order: [[0, 'desc']],
                ajax: {
                    url: '{{ route('orders.index') }}',
                    data: function (d) { d.status = $('#filterStatus').val(); }
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

            $('#filterStatus').on('change', function () {
                $('#orderTable').DataTable().ajax.reload();
            });
        });
    </script>
@endsection
