@extends('admin.pages.master')
@section('title', ($zone->exists ? 'Edit' : 'Add') . ' Delivery Zone')

@section('content')

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">{{ $zone->exists ? 'Edit' : 'Add New' }} Delivery Zone</h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ $zone->exists ? route('delivery-zones.update') : route('delivery-zones.store') }}">
                            @csrf
                            @if ($zone->exists)<input type="hidden" name="id" value="{{ $zone->id }}">@endif
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" required maxlength="100" value="{{ old('name', $zone->name) }}" placeholder="e.g. Leeds central">
                                    @error('name')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sort order</label>
                                    <input type="number" name="sort_order" class="form-control" min="0" value="{{ old('sort_order', $zone->sort_order ?? 0) }}">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Postcode prefixes <small class="text-muted">(one outward code per line, e.g. LS1)</small></label>
                                    @php $prefixText = old('prefixes', $zone->exists ? $zone->postcodes->pluck('prefix')->implode("\n") : ''); @endphp
                                    <textarea name="prefixes" class="form-control" rows="5" maxlength="2000" placeholder="LS1&#10;LS2&#10;YO1">{{ $prefixText }}</textarea>
                                    @error('prefixes')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-12">
                                    <div class="form-check form-switch" dir="ltr">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $zone->is_active ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label">Active (zone is served)</label>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save zone</button>
                                    <a href="{{ route('delivery-zones.index') }}" class="btn btn-secondary">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
