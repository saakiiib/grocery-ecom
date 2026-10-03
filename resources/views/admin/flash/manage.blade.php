@extends('admin.pages.master')
@section('title', ($sale->exists ? 'Edit' : 'Add') . ' Flash Sale')

@section('content')

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">{{ $sale->exists ? 'Edit' : 'Add New' }} Flash Sale</h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ $sale->exists ? route('flash.update') : route('flash.store') }}">
                            @csrf
                            @if ($sale->exists)<input type="hidden" name="id" value="{{ $sale->id }}">@endif
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Product</label>
                                    <select name="product_id" id="flashProduct" class="form-control select2" required>
                                        <option value="">Select product</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}" @selected(old('product_id', $sale->product_id) == $p->id)>{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('product_id')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pack</label>
                                    <select name="product_variant_id" id="flashVariant" class="form-control">
                                        <option value="">All packs</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Parent offer <small class="text-muted">(optional — dates and switch follow the campaign)</small></label>
                                    <select name="offer_id" class="form-control">
                                        <option value="">Standalone (own dates only)</option>
                                        @foreach ($offers as $parent)
                                            <option value="{{ $parent->id }}" @selected(old('offer_id', request('offer_id', $sale->offer_id)) == $parent->id)>{{ $parent->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Flash price (£)</label>
                                    <input type="number" name="promo_price" class="form-control" required step="0.01" min="0.01" value="{{ old('promo_price', $sale->promo_price) }}">
                                    @error('promo_price')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Starts at</label>
                                    <input type="datetime-local" name="starts_at" class="form-control" required value="{{ old('starts_at', $sale->starts_at?->format('Y-m-d\TH:i')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ends at</label>
                                    <input type="datetime-local" name="ends_at" class="form-control" required value="{{ old('ends_at', $sale->ends_at?->format('Y-m-d\TH:i')) }}">
                                    @error('ends_at')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-12">
                                    <div class="form-check form-switch" dir="ltr">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $sale->status ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label">Active (counts down on the storefront while live)</label>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save flash sale</button>
                                    <a href="{{ route('flash.index') }}" class="btn btn-secondary">Cancel</a>
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
            var selectedVariant = "{{ old('product_variant_id', $sale->product_variant_id ?? '') }}";
            function loadVariants(productId, keep) {
                var sel = $('#flashVariant');
                sel.empty().append('<option value="">All packs</option>');
                if (!productId) return;
                $.get('/admin/products/' + productId + '/variants', function(variants) {
                    variants.forEach(function(v) {
                        var label = (v.combination || v.sku || ('#' + v.id)) + ' — £' + v.mrp;
                        sel.append('<option value="' + v.id + '"' + (String(v.id) === String(keep) ? ' selected' : '') + '>' + label + '</option>');
                    });
                });
            }
            $('#flashProduct').on('change', function() { loadVariants($(this).val(), ''); });
            loadVariants($('#flashProduct').val(), selectedVariant);
        });
    </script>
@endsection
