@extends('admin.pages.master')
@section('title', 'Import Preview')
@section('content')

<div class="container-fluid">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <a href="{{ route('products.index') }}" class="btn btn-light btn-sm">← Back to Products</a>
            <h4 class="mt-2 mb-0">Import Preview <small class="text-muted">nothing is saved yet</small></h4>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-2"><div class="card"><div class="card-body text-center"><h3 class="mb-0">{{ $stats['products_new'] }}</h3><small class="text-muted">new products</small></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body text-center"><h3 class="mb-0">{{ $stats['products_updated'] }}</h3><small class="text-muted">updated products</small></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body text-center"><h3 class="mb-0">{{ $stats['variants_new'] }}</h3><small class="text-muted">new variants</small></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body text-center"><h3 class="mb-0">{{ $stats['variants_updated'] }}</h3><small class="text-muted">updated variants</small></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body text-center"><h3 class="mb-0">{{ count($stats['categories_new']) }}</h3><small class="text-muted">new categories</small></div></div></div>
        <div class="col-md-2"><div class="card"><div class="card-body text-center"><h3 class="mb-0">{{ count($stats['values_new']) }}</h3><small class="text-muted">new option values</small></div></div></div>
    </div>

    @if ($stats['categories_new'])
        <div class="alert alert-info py-2">New categories to be created: <strong>{{ implode(', ', $stats['categories_new']) }}</strong></div>
    @endif
    @if ($stats['values_new'])
        <div class="alert alert-info py-2">New option values to be created: <strong>{{ implode(', ', $stats['values_new']) }}</strong></div>
    @endif

    <div class="alert alert-info py-2">Photos are not part of Excel import — manage them on the <a href="{{ route('products.bulkPhotos') }}">Bulk Photos</a> page.</div>

    @if ($errors)
        @if (collect($errors)->contains(fn ($e) => ! $e['row']))
            <div class="alert alert-danger">This file has structural problems (rows marked —). <strong>Confirm is blocked</strong> — fix the file and upload again.</div>
        @endif
        <div class="card mb-3 border-danger">
            <div class="card-header bg-danger text-white">Skipped rows ({{ count($errors) }}) — fix in Excel and re-upload, or confirm to import the valid rows only</div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th style="width:80px;">Row</th><th>Problem</th></tr></thead>
                    <tbody>
                        @foreach ($errors as $e)
                            <tr><td>{{ $e['row'] ?: '—' }}</td><td>{{ $e['message'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header"><h5 class="card-title mb-0">Valid rows ({{ count($rows) }}){{ count($rows) > 100 ? ' — first 100 shown' : '' }}</h5></div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-bordered table-striped mb-0">
                <thead><tr><th>Row</th><th>SKU</th><th>Product</th><th>Category</th><th>Options</th><th>MRP</th><th>Offer</th><th>Stock</th><th>Images</th><th>Action</th></tr></thead>
                <tbody>
                    @foreach ($rows->take(100) as $r)
                        <tr>
                            <td>{{ $r['line'] }}</td>
                            <td>{{ $r['sku'] ?? '—' }}</td>
                            <td>{{ $r['product_name'] }} {!! $r['product_id'] ? '<span class="badge bg-secondary">update</span>' : '<span class="badge bg-success">new</span>' !!}</td>
                            <td>{{ $r['category']['name'] }} {!! $r['category']['new'] ? '<span class="badge bg-success">new</span>' : '' !!}</td>
                            <td>{{ collect($r['group_values'])->map(fn ($gv) => $gv['label'].($gv['new'] ? ' (new)' : ''))->join(' / ') ?: '—' }}</td>
                            <td>£{{ number_format($r['mrp'], 2) }}</td>
                            <td>{{ $r['offer_price'] !== null ? '£'.number_format($r['offer_price'], 2) : '—' }}</td>
                            <td>{{ $r['in_stock'] ? 'yes' : 'no' }}</td>
                            <td class="small">
                                @php $hs = $r['hero_source']; $vs = $r['variant_source']; @endphp
                                H: {{ $hs['kind'] === 'none' ? '—' : ($hs['kind'] === 'as-is' ? 'URL/kept' : '✅ '.basename($hs['file'])) }}<br>
                                V: {{ ! $vs ? '—' : ($vs['kind'] === 'none' ? '—' : ($vs['kind'] === 'as-is' ? 'URL/kept' : '✅ '.basename($vs['file']))) }}
                            </td>
                            <td>{!! $r['variant_id'] ? '<span class="badge bg-secondary">update</span>' : '<span class="badge bg-success">new</span>' !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if (count($rows))
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('products.importConfirm') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="disable_missing" value="1" id="disableMissing">
                        <label class="form-check-label" for="disableMissing">Disable missing variants <small class="text-muted">variants in the shop but absent from this sheet become disabled</small></label>
                    </div>
                    <button type="submit" class="btn btn-success">Confirm import ({{ count($rows) }} rows)</button>
                    <a href="{{ route('products.index') }}" class="btn btn-light">Cancel</a>
                </form>
            </div>
        </div>
    @endif
</div>

@endsection
