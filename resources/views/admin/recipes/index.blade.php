@extends('admin.pages.master')
@section('title', 'Recipes')

@section('content')

    <div class="container-fluid">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Recipes</h4>
                <a href="{{ route('admin.recipes.create') }}" class="btn btn-primary btn-sm">Add recipe</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Ingredients</th>
                                <th>Visible</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recipes as $r)
                                <tr>
                                    <td>{{ $r->id }}</td>
                                    <td>{{ $r->title }}<br><small class="text-muted">/{{ $r->slug }}</small></td>
                                    <td>{{ $r->ingredients_count }}</td>
                                    <td>{{ $r->status ? 'Yes' : 'No' }}</td>
                                    <td class="d-flex gap-2">
                                        <a href="{{ route('admin.recipes.edit', $r->id) }}" class="btn btn-sm btn-soft-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.recipes.delete', $r->id) }}" onsubmit="return confirm('Delete this recipe?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-soft-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No recipes yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $recipes->links() }}
            </div>
        </div>
    </div>

@endsection
