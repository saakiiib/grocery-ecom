@extends('admin.pages.master')
@section('title', 'Dashboard')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-1">Welcome</h4>
                        <p class="text-muted mb-0">Here's a quick overview of your shop.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card"><div class="card-body">
                    <p class="text-muted mb-1">Orders today</p>
                    <h3 class="mb-0">{{ $stats['today_orders'] }}</h3>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body">
                    <p class="text-muted mb-1">Revenue today</p>
                    <h3 class="mb-0">£{{ number_format($stats['today_revenue'], 2) }}</h3>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body">
                    <p class="text-muted mb-1">Open orders</p>
                    <h3 class="mb-0">{{ $stats['open_orders'] }}</h3>
                    <a href="{{ route('orders.index') }}" class="small">View orders →</a>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body">
                    <p class="text-muted mb-1">Registered shoppers</p>
                    <h3 class="mb-0">{{ $stats['customers'] }}</h3>
                </div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card border-warning"><div class="card-body">
                    <p class="text-muted mb-1">Online payments pending</p>
                    <h3 class="mb-0">{{ $needs['unpaid_online'] }}</h3>
                    <a href="{{ route('orders.index') }}" class="small">Chase in orders →</a>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning"><div class="card-body">
                    <p class="text-muted mb-1">Packs out of stock</p>
                    <h3 class="mb-0">{{ $needs['out_of_stock'] }}</h3>
                    <a href="{{ route('stock-alerts.index') }}" class="small">View stock alerts →</a>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning"><div class="card-body">
                    <p class="text-muted mb-1">Messages (last 7 days)</p>
                    <h3 class="mb-0">{{ $needs['new_messages'] }}</h3>
                    <a href="{{ route('admin.contacts.index') }}" class="small">Read messages →</a>
                </div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4 class="card-title mb-0 flex-grow-1">Latest orders</h4>
                        <a href="{{ route('orders.index') }}" class="btn btn-soft-secondary btn-sm">All orders</a>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <select id="dashboardStatus" class="form-control select2">
                                    <option value="">All statuses</option>
                                    @foreach ($orderStatuses as $st)
                                        <option value="{{ $st->slug }}">{{ $st->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="dashboardOrderTable" class="table table-bordered table-striped" style="width: 100%">
                                <thead>
                                    <tr>
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
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">Orders by status</h4></div>
                    <div class="card-body">
                        @forelse ($orderStatuses as $st)
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge me-2" style="background:{{ $st->color ?? '#1A2E22' }};">{{ $st->name }}</span>
                                <span class="flex-grow-1"></span>
                                <strong>{{ $statusCounts[$st->slug] ?? 0 }}</strong>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No order statuses set up yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4 class="card-title mb-0 flex-grow-1">Top products · last 7 days</h4>
                        <span class="badge bg-success">£{{ number_format($weekRevenue, 2) }} booked</span>
                    </div>
                    <div class="card-body">
                        @forelse ($topProducts as $i => $p)
                            <div class="d-flex align-items-center mb-2">
                                <span class="text-muted me-2">{{ $i + 1 }}.</span>
                                <span class="flex-grow-1">{{ $p->product_name }}</span>
                                <span class="text-muted me-2">{{ $p->units }} sold</span>
                                <strong>£{{ number_format($p->revenue, 2) }}</strong>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No sales in the last 7 days.</p>
                        @endforelse
                    </div>
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
            $('.select2').select2({ width: '100%' });

            $('#dashboardOrderTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                order: [[6, 'desc']],
                ajax: {
                    url: '{{ route('orders.index') }}',
                    data: function (d) { d.status = $('#dashboardStatus').val(); }
                },
                columns: [
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

            $('#dashboardStatus').on('change', function () {
                $('#dashboardOrderTable').DataTable().ajax.reload();
            });
        });
    </script>
@endsection
