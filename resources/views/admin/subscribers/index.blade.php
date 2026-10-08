@extends('admin.pages.master')
@section('title', 'Newsletter Subscribers')

@section('content')

    <div class="container-fluid">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Newsletter Subscribers <small class="text-muted">— {{ $subscribers->total() }} total</small></h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Email</th>
                                <th>Source</th>
                                <th>Active</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subscribers as $s)
                                <tr>
                                    <td>{{ $s->id }}</td>
                                    <td>{{ $s->email }}</td>
                                    <td>{{ $s->source }}</td>
                                    <td>{{ $s->is_active ? 'Yes' : 'No' }}</td>
                                    <td>{{ $s->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="d-flex gap-2">
                                        <form method="POST" action="{{ route('subscribers.toggleStatus') }}">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $s->id }}">
                                            <button type="submit" class="btn btn-sm {{ $s->is_active ? 'btn-soft-warning' : 'btn-soft-success' }}">{{ $s->is_active ? 'Deactivate' : 'Activate' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('subscribers.delete', $s->id) }}" onsubmit="return confirm('Delete this subscriber?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-soft-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">No subscribers yet — the footer form feeds this list.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $subscribers->links() }}
            </div>
        </div>
    </div>

@endsection
