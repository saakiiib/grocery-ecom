@extends('admin.pages.master')
@section('title', ($offer->exists ? 'Edit' : 'Add') . ' BOGO Offer')

@section('content')

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">{{ $offer->exists ? 'Edit' : 'Add New' }} BOGO Offer</h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ $offer->exists ? route('bogo.update') : route('bogo.store') }}">
                            @csrf
                            @if ($offer->exists)<input type="hidden" name="id" value="{{ $offer->id }}">@endif
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Product</label>
                                    <select name="product_id" id="bogoProduct" class="form-control select2" required>
                                        <option value="">Select product</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}" @selected(old('product_id', $offer->product_id) == $p->id)>{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('product_id')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pack</label>
                                    <select name="product_variant_id" id="bogoVariant" class="form-control">
                                        <option value="">All packs</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Parent offer <small class="text-muted">(optional — dates and switch follow the campaign)</small></label>
                                    <select name="offer_id" class="form-control">
                                        <option value="">Standalone (own dates only)</option>
                                        @foreach ($offers as $parent)
                                            <option value="{{ $parent->id }}" @selected(old('offer_id', request('offer_id', $offer->offer_id)) == $parent->id)>{{ $parent->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Buy qty</label>
                                    <input type="number" name="buy_qty" class="form-control" required min="1" max="99" value="{{ old('buy_qty', $offer->buy_qty ?? 2) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Free qty</label>
                                    <input type="number" name="free_qty" class="form-control" required min="1" max="99" value="{{ old('free_qty', $offer->free_qty ?? 1) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Starts at <small class="text-muted">(optional)</small></label>
                                    <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', $offer->starts_at?->format('Y-m-d\TH:i')) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Ends at <small class="text-muted">(optional)</small></label>
                                    <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', $offer->ends_at?->format('Y-m-d\TH:i')) }}">
                                    @error('ends_at')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-12">
                                    <div class="form-check form-switch" dir="ltr">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $offer->status ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label">Active (applies in the bag immediately)</label>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save offer</button>
                                    <a href="{{ route('bogo.index') }}" class="btn btn-secondary">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $('.select2').select2({ width: '100%' });
            var selectedVariant = "{{ old('product_variant_id', $offer->product_variant_id ?? '') }}";
            function loadVariants(productId, keep) {
                var sel = $('#bogoVariant');
                sel.empty().append('<option value="">All packs</option>');
                if (!productId) return;
                $.get('/admin/products/' + productId + '/variants', function(variants) {
                    variants.forEach(function(v) {
                        var label = (v.combination || v.sku || ('#' + v.id)) + ' — £' + v.mrp;
                        sel.append('<option value="' + v.id + '"' + (String(v.id) === String(keep) ? ' selected' : '') + '>' + label + '</option>');
                    });
                });
            }
            $('#bogoProduct').on('change', function() { loadVariants($(this).val(), ''); });
            loadVariants($('#bogoProduct').val(), selectedVariant);
        });
    </script>
@endsection
