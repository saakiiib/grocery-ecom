@extends('admin.pages.master')
@section('title', 'Weekly Repeats')

@section('content')

    <div class="container-fluid">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Weekly Repeats <small class="text-muted">— {{ $repeats->total() }} total · runs via <code>php artisan app:run-repeats</code></small></h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Shopper</th>
                                <th>Deliver to</th>
                                <th>Items</th>
                                <th>Next run</th>
                                <th>Active</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($repeats as $r)
                                <tr>
                                    <td>{{ $r->id }}</td>
                                    <td>{{ $r->user?->name ?? $r->name }}<br><small class="text-muted">{{ $r->email }}</small></td>
                                    <td>{{ $r->address }}, {{ $r->postcode }}</td>
                                    <td>{{ is_array($r->items) ? count($r->items) : 0 }} lines · {{ strtoupper($r->payment_method) }}</td>
                                    <td>{{ $r->next_run_at->format('D j M') }}</td>
                                    <td>{{ $r->is_active ? 'Yes' : 'No' }}</td>
                                    <td class="d-flex gap-2">
                                        <form method="POST" action="{{ route('repeats.toggleStatus') }}">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $r->id }}">
                                            <button type="submit" class="btn btn-sm {{ $r->is_active ? 'btn-soft-warning' : 'btn-soft-success' }}">{{ $r->is_active ? 'Pause' : 'Resume' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('repeats.delete', $r->id) }}" onsubmit="return confirm('Delete this repeat?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-soft-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">No repeats yet — shoppers start them from any delivered order.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $repeats->links() }}
            </div>
        </div>
    </div>

@endsection
