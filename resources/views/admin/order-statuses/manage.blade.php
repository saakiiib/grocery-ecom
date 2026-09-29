@extends('admin.pages.master')
@section('title', 'Edit Order Status')

@section('content')

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">Edit Status: {{ $status->name }} <small class="text-muted">(<code>{{ $status->slug }}</code> — never changes)</small></h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('order-statuses.update') }}">
                            @csrf
                            <input type="hidden" name="id" value="{{ $status->id }}">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Display name</label>
                                    <input type="text" name="name" class="form-control" required maxlength="100" value="{{ old('name', $status->name) }}">
                                    @error('name')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Badge colour</label>
                                    <input type="color" name="color" class="form-control form-control-color" required value="{{ old('color', $status->color) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Sort order</label>
                                    <input type="number" name="sort_order" class="form-control" min="0" value="{{ old('sort_order', $status->sort_order) }}">
                                </div>
                                <div class="col-md-12">
                                    <div class="form-check form-switch" dir="ltr">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $status->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label">Active (offered in the status dropdown)</label>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save status</button>
                                    <a href="{{ route('order-statuses.index') }}" class="btn btn-secondary">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
