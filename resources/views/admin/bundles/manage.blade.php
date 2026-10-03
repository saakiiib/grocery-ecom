@extends('admin.pages.master')
@section('title', ($bundle->exists ? 'Edit' : 'Add') . ' Bundle')

@section('content')

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">{{ $bundle->exists ? 'Edit' : 'Add New' }} Bundle</h4></div>
                    <div class="card-body">
                        @php
                            $selCats = old('categories', $bundle->exists ? $bundle->categories->pluck('id')->all() : []);
                            $selVars = old('variants', $bundle->exists ? $bundle->variants->pluck('id')->all() : []);
                        @endphp
                        <form method="POST" action="{{ $bundle->exists ? route('bundles.update') : route('bundles.store') }}">
                            @csrf
                            @if ($bundle->exists)<input type="hidden" name="id" value="{{ $bundle->id }}">@endif
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" required maxlength="120" value="{{ old('name', $bundle->name) }}" placeholder="e.g. Weekend Rice Deal">
                                    @error('name')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Any (qty)…</label>
                                    <input type="number" name="required_qty" class="form-control" required min="2" max="99" value="{{ old('required_qty', $bundle->required_qty ?? 3) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">…for £</label>
                                    <input type="number" name="bundle_price" class="form-control" required step="0.01" min="0.01" value="{{ old('bundle_price', $bundle->bundle_price) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Starts at <small class="text-muted">(optional)</small></label>
                                    <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', $bundle->starts_at?->format('Y-m-d\TH:i')) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Ends at <small class="text-muted">(optional)</small></label>
                                    <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', $bundle->ends_at?->format('Y-m-d\TH:i')) }}">
                                    @error('ends_at')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mt-4" dir="ltr">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $bundle->status ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label">Active</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Parent offer <small class="text-muted">(optional — dates and switch follow the campaign)</small></label>
                                    <select name="offer_id" class="form-control">
                                        <option value="">Standalone (own dates only)</option>
                                        @foreach ($offers as $parent)
                                            <option value="{{ $parent->id }}" @selected(old('offer_id', request('offer_id', $bundle->offer_id)) == $parent->id)>{{ $parent->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Categories <small class="text-muted">(whole ranges join, future packs included)</small></label>
                                    <div class="border rounded p-2" style="max-height:220px;overflow:auto;">
                                        @foreach ($categories as $c)
                                            <div class="form-check">
                                                <input type="checkbox" name="categories[]" value="{{ $c->id }}" class="form-check-input" id="cat{{ $c->id }}" @checked(in_array($c->id, $selCats))>
                                                <label class="form-check-label" for="cat{{ $c->id }}"><strong>{{ $c->name }}</strong></label>
                                            </div>
                                            @foreach ($c->children as $kid)
                                                <div class="form-check ms-4">
                                                    <input type="checkbox" name="categories[]" value="{{ $kid->id }}" class="form-check-input" id="cat{{ $kid->id }}" @checked(in_array($kid->id, $selCats))>
                                                    <label class="form-check-label" for="cat{{ $kid->id }}">{{ $kid->name }}</label>
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Extra packs <small class="text-muted">(specific SKUs on top)</small></label>
                                    <input type="text" id="packFilter" class="form-control mb-2" placeholder="Filter packs…">
                                    <div class="border rounded p-2" style="max-height:220px;overflow:auto;" id="packList">
                                        @foreach ($products as $p)
                                            @foreach ($p->variants as $v)
                                                <div class="form-check" data-pack-name="{{ strtolower($p->name.' '.($v->sku ?? '')) }}">
                                                    <input type="checkbox" name="variants[]" value="{{ $v->id }}" class="form-check-input" id="var{{ $v->id }}" @checked(in_array($v->id, $selVars))>
                                                    <label class="form-check-label" for="var{{ $v->id }}">{{ $p->name }} — {{ $v->sku ?? '#'.$v->id }} (£{{ number_format($v->mrp, 2) }})</label>
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save bundle</button>
                                    <a href="{{ route('bundles.index') }}" class="btn btn-secondary">Cancel</a>
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
            $('#packFilter').on('input', function() {
                var q = $(this).val().toLowerCase();
                $('#packList [data-pack-name]').each(function() {
                    $(this).toggle($(this).data('pack-name').indexOf(q) !== -1);
                });
            });
        });
    </script>
@endsection
