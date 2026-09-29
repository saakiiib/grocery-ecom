@extends('admin.pages.master')
@section('title', ($slot->exists ? 'Edit' : 'Add') . ' Delivery Slot')

@section('content')

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">{{ $slot->exists ? 'Edit' : 'Add New' }} Delivery Slot</h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ $slot->exists ? route('delivery-slots.update') : route('delivery-slots.store') }}">
                            @csrf
                            @if ($slot->exists)<input type="hidden" name="id" value="{{ $slot->id }}">@endif
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" required maxlength="100" value="{{ old('name', $slot->name) }}" placeholder="e.g. Morning">
                                    @error('name')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Starts at</label>
                                    <input type="time" name="starts_at" class="form-control" required value="{{ old('starts_at', $slot->starts_at) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Ends at</label>
                                    <input type="time" name="ends_at" class="form-control" required value="{{ old('ends_at', $slot->ends_at) }}">
                                    @error('ends_at')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fee (£)</label>
                                    <input type="number" name="fee" class="form-control" required step="0.01" min="0" max="999" value="{{ old('fee', $slot->fee ?? 0) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Same-day cutoff hour <small class="text-muted">(0–23)</small></label>
                                    <input type="number" name="cutoff_hour" class="form-control" required min="0" max="23" value="{{ old('cutoff_hour', $slot->cutoff_hour ?? 20) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sort order</label>
                                    <input type="number" name="sort_order" class="form-control" min="0" value="{{ old('sort_order', $slot->sort_order ?? 0) }}">
                                </div>
                                <div class="col-md-12">
                                    <div class="form-check form-switch" dir="ltr">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $slot->is_active ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label">Active (visible at checkout)</label>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save slot</button>
                                    <a href="{{ route('delivery-slots.index') }}" class="btn btn-secondary">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
