@extends('admin.pages.master')
@section('title', 'Bulk Photos')
@section('content')

<div class="container-fluid">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <a href="{{ route('products.index') }}" class="btn btn-light btn-sm">← Back to Products</a>
            <h4 class="mt-2 mb-0">Bulk Photos</h4>
            <p class="text-muted mb-0">Drop product photos — no Excel, no ZIP, no renaming folders. Photos are matched by filename: name each file with its <strong>SKU</strong> (e.g. <code>LAMB-500.jpg</code>) or the product slug for a main photo.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ implode(' ', $errors->all()) }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <form id="dropForm" method="POST" action="{{ route('products.bulkPhotosUpload') }}" enctype="multipart/form-data">
                        @csrf
                        <div id="dropzone" class="border border-2 border-dashed rounded p-5 text-center" style="cursor:pointer; border-color:#c9d6cf !important;">
                            <div class="fs-1">📷</div>
                            <p class="mb-1"><strong>Drop photos here</strong> or click to choose (up to 20 at once, jpg / png / webp, max 4MB each)</p>
                            <p class="text-muted small mb-0">Tip: filename = SKU, e.g. <code>EGF89913.jpg</code> or <code>EGF89913-front.png</code></p>
                            <input type="file" id="photoInput" name="photos[]" accept=".jpg,.jpeg,.png,.webp" multiple hidden>
                        </div>
                        <div id="picked" class="d-flex flex-wrap gap-2 mt-3"></div>
                        <button type="submit" id="uploadBtn" class="btn btn-primary mt-3" disabled>Upload &amp; match</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Catalog</h5></div>
                <div class="card-body">
                    <p class="mb-1">Products: <strong>{{ $stats['products'] }}</strong></p>
                    <p class="mb-1">Variants: <strong>{{ $stats['variants'] }}</strong></p>
                    <p class="mb-1">With main photo: <strong>{{ $stats['with_hero'] }}</strong></p>
                    <p class="mb-0">Variants with photo: <strong>{{ $stats['variants_with_photo'] }}</strong></p>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header"><h5 class="card-title mb-0">How matching works</h5></div>
                <div class="card-body small text-muted">
                    <p class="mb-1">1. Exact SKU in the filename → that size's photo.</p>
                    <p class="mb-1">2. SKU anywhere in the filename → same (longest SKU wins).</p>
                    <p class="mb-1">3. Product slug in the filename → main photo.</p>
                    <p class="mb-0">4. Anything else → you pick the product on the next screen. Nothing is saved until you press Confirm.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
$(function () {
    const dz = $('#dropzone'), input = $('#photoInput'), picked = $('#picked'), btn = $('#uploadBtn');
    dz.on('click', () => input.trigger('click'));
    ['dragover', 'dragenter'].forEach(e => dz.on(e, ev => { ev.preventDefault(); dz.addClass('bg-light'); }));
    ['dragleave', 'drop'].forEach(e => dz.on(e, ev => { ev.preventDefault(); dz.removeClass('bg-light'); }));
    dz.on('drop', ev => { input[0].files = ev.originalEvent.dataTransfer.files; render(); });
    input.on('change', render);
    function render() {
        picked.empty();
        const files = [...(input[0].files || [])].slice(0, 20);
        files.forEach(f => {
            const url = URL.createObjectURL(f);
            picked.append(`<img src="${url}" title="${f.name}" style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">`);
        });
        btn.prop('disabled', files.length === 0);
        btn.text(files.length ? `Upload & match (${files.length})` : 'Upload & match');
    }
});
</script>
@endsection
