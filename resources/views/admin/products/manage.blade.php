@extends('admin.pages.master')
@section('title', 'Manage Product — ' . $product->name)
@section('content')

@php
    $overriding = $product->optionGroups->isNotEmpty();
    $effectiveGroups = $product->effectiveOptionGroups();
    $overrideIds = $product->optionGroups->pluck('id')->all();
@endphp

<div class="container-fluid">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <a href="{{ route('products.index') }}" class="btn btn-light btn-sm">← Back to Products</a>
            <h4 class="mt-2 mb-0">{{ $product->name }} <small class="text-muted">{{ $product->priceRange() }}</small></h4>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button">1. Basic + SEO</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-images" type="button">2. Images</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-variants" type="button">3. Variants &amp; Prices</button></li>
            </ul>
        </div>
        <div class="card-body tab-content">
            <div class="tab-pane fade show active" id="tab-basic">
                <form id="basicForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="codeid" value="{{ $product->id }}">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" value="{{ $product->name }}"></div>
                        <div class="col-md-6"><label class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-control select2" name="category_id">
                                <option value="">Select</option>
                                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected($product->category_id == $c->id)>{{ $c->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Card subtitle <small class="text-muted">one line under the name on cards</small></label><input type="text" class="form-control" name="tagline" maxlength="150" value="{{ $product->tagline }}"></div>
                        <div class="col-md-6"><div class="form-check mt-4">
                            <input type="checkbox" class="form-check-input" name="is_featured" value="1" @checked($product->is_featured)>
                            <label class="form-check-label">Featured product</label>
                        </div></div>
                        <div class="col-12"><label class="form-label">Key points <small class="text-muted">one per line → shown as ✓ bullets</small></label><textarea class="form-control" name="highlights" rows="3" placeholder="100% British halal lamb&#10;Matured 7 days for flavour&#10;Freezer-friendly">{{ $product->highlights }}</textarea></div>
                        <div class="col-12"><label class="form-label">Full description</label><textarea class="form-control summernote" name="description" rows="4">{{ $product->description }}</textarea></div>
                        <div class="col-12"><hr><h6>Extra details <small class="text-muted">cooking suggestion, allergy advice, storage… anything, in your order</small></h6>
                            <div id="attrList">
                                @foreach ($product->extraAttributes as $a)
                                    <div class="row g-2 mb-2 attr-row">
                                        <div class="col-md-4"><input type="text" class="form-control attr-label" placeholder="Label e.g. Cooking suggestion" value="{{ $a->label }}"></div>
                                        <div class="col-md-7"><textarea class="form-control attr-value" rows="1" placeholder="Detail…">{{ $a->value }}</textarea></div>
                                        <div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm attr-del">✕</button></div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" id="attrAdd" class="btn btn-sm btn-outline-secondary">+ Add detail</button>
                            <button type="button" id="attrSave" class="btn btn-sm btn-outline-primary ms-2">Save details</button>
                        </div>
                        <div class="col-12"><label class="form-label">Hero Image</label><input type="file" class="form-control" name="hero_image" accept="image/*">
                            @if ($product->hero_image)<img src="{{ $product->hero_image }}" class="img-thumbnail mt-2" style="max-width:200px;">
                            <div class="mt-2"><button type="button" class="btn btn-sm btn-outline-danger manage-remove-file" data-field="hero_image">Remove image</button></div>@endif</div>
                        <div class="col-12"><hr><h6>SEO (frontend meta tags)</h6></div>
                        <div class="col-md-6"><label class="form-label">Meta Title</label><input type="text" class="form-control" name="meta_title" value="{{ $product->meta_title }}"></div>
                        <div class="col-md-6"><label class="form-label">Meta Keywords</label><input type="text" class="form-control" name="meta_keywords" value="{{ $product->meta_keywords }}"></div>
                        <div class="col-md-8"><label class="form-label">Meta Description</label><textarea class="form-control" name="meta_description" rows="2">{{ $product->meta_description }}</textarea></div>
                        <div class="col-md-4"><label class="form-label">Meta Image</label><input type="file" class="form-control" name="meta_image" accept="image/*">
                            @if ($product->meta_image)<img src="{{ $product->meta_image }}" class="img-thumbnail mt-2" style="max-width:150px;">
                            <div class="mt-2"><button type="button" class="btn btn-sm btn-outline-danger manage-remove-file" data-field="meta_image">Remove image</button></div>@endif</div>
                    </div>
                    <div class="text-end mt-3"><button type="button" id="saveBasic" class="btn btn-primary">Save Basic + SEO</button></div>
                </form>
            </div>

            <div class="tab-pane fade" id="tab-images">
                <form id="imgForm" class="row g-2 mb-3">
                    <div class="col-md-5"><input type="file" class="form-control" id="imgFile" accept="image/*" required></div>
                    <div class="col-md-5"><input type="text" class="form-control" id="imgCaption" placeholder="Caption (optional)"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Add Image</button></div>
                </form>
                <div id="imgList" class="row g-2"></div>
            </div>

            <div class="tab-pane fade" id="tab-variants">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6>Option groups for this product</h6>
                        <p class="text-muted small mb-2">
                            @if ($overriding)
                                Using <strong>product overrides</strong>. Untick all + save to inherit the category template again.
                            @else
                                Inheriting from category <strong>{{ $product->category?->name ?? '—' }}</strong>. Tick groups + save to override for this product only.
                            @endif
                        </p>
                        <div id="groupChecks" class="d-flex flex-wrap gap-3">
                            @foreach ($allGroups as $g)
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input group-check" value="{{ $g->id }}" id="gc{{ $g->id }}"
                                        @checked($overriding ? in_array($g->id, $overrideIds) : $effectiveGroups->contains('id', $g->id))>
                                    <label class="form-check-label" for="gc{{ $g->id }}">{{ $g->name }}</label>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" id="saveGroups" class="btn btn-sm btn-outline-primary mt-2">Save option groups</button>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 id="variantFormTitle">Add variant</h6>
                        <form id="variantForm" enctype="multipart/form-data">
                            <input type="hidden" id="variant_id">
                            <div class="row g-3">
                                <div class="col-md-3"><label class="form-label">SKU</label><input type="text" class="form-control" id="v_sku" placeholder="EGF89913"></div>
                                <div class="col-md-3"><label class="form-label">MRP (£) *</label><input type="number" step="0.01" min="0" class="form-control" id="v_mrp"></div>
                                <div class="col-md-3"><label class="form-label">Offer price (£)</label><input type="number" step="0.01" min="0" class="form-control" id="v_offer"></div>
                                <div class="col-md-3"><label class="form-label">Photo <small class="text-muted">optional</small></label><input type="file" class="form-control" id="v_image" accept="image/*"></div>
                                <div class="col-md-3"><div class="form-check mt-4">
                                    <input type="checkbox" class="form-check-input" id="v_stock" checked>
                                    <label class="form-check-label" for="v_stock">In stock</label>
                                </div></div>
                            </div>
                            <div class="row g-3 mt-1" id="valueChecks">
                                @foreach ($effectiveGroups as $g)
                                    <div class="col-md-4" data-group="{{ $g->id }}">
                                        <label class="form-label">{{ $g->name }} <small class="text-muted">pick one</small></label>
                                        @foreach ($g->values as $v)
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input value-check" value="{{ $v->id }}" data-label="{{ $v->label }}" data-group="{{ $g->id }}" id="vv{{ $v->id }}">
                                                <label class="form-check-label" for="vv{{ $v->id }}">{{ $v->label }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm" id="variantSaveBtn">Add variant</button>
                                <button type="button" class="btn btn-light btn-sm" id="variantResetBtn">Reset</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="generateBtn">Generate missing combinations (MRP £0)</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped w-100">
                        <thead><tr><th>Combination</th><th>SKU</th><th>MRP</th><th>Offer</th><th>Selling</th><th>Stock</th><th>Default</th><th style="width:130px;">Action</th></tr></thead>
                        <tbody id="variantRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
const PID = {{ $product->id }};
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $('.select2').select2({ width: '100%' });
    $('.summernote').summernote({ height: 150 });

    $('#saveBasic').click(function () {
        const fd = new FormData(document.getElementById('basicForm'));
        fd.set('description', $('[name=description]').summernote('code'));
        if (!$('[name=is_featured]').is(':checked')) fd.set('is_featured', 0);
        showLoader();
        $.ajax({ url: "{{ route('products.update') }}", type: 'POST', data: fd, contentType: false, processData: false,
            success: d => { hideLoader(); showSuccess(d.message); },
            error: xhr => { hideLoader(); showError(xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : 'Error'); } });
    });

    function loadImg() { $.get(`/admin/products/${PID}/images`, list => { $('#imgList').html(list.map(i => `<div class="col-md-3"><div class="card"><img src="${i.preview}" class="card-img-top"><div class="card-body p-2"><input class="form-control form-control-sm mb-1" value="${i.caption ?? ''}" onchange="updImg(${i.id},this.value)"><button class="btn btn-sm btn-danger" onclick="delImg(${i.id})">Delete</button></div></div></div>`).join('') || '<p class="text-muted">No images yet. First image acts as gallery backup to hero.</p>'); }); }

    loadImg();

    $('#imgForm').submit(e => { e.preventDefault(); const fd = new FormData(); fd.append('image', $('#imgFile')[0].files[0]); fd.append('caption', $('#imgCaption').val()); $.ajax({ url: `/admin/products/${PID}/images`, type: 'POST', data: fd, contentType: false, processData: false, success: d => { showSuccess(d.message); $('#imgForm')[0].reset(); loadImg(); }, error: xhr => showError(xhr.responseJSON?.message ?? 'Error') }); });

    window.delImg = id => $.ajax({ url: `/admin/product-images/${id}`, type: 'DELETE', success: d => { showSuccess(d.message); loadImg(); } });
    window.updImg = (id, caption) => $.post(`/admin/product-images/${id}`, { caption }, () => loadImg());

    $('.manage-remove-file').click(function () {
        const field = $(this).data('field');
        showLoader();
        $.ajax({
            url: `/admin/products/${PID}/file`,
            type: 'DELETE',
            data: { field: field },
            success: d => { hideLoader(); showSuccess(d.message); location.reload(); },
            error: () => { hideLoader(); showError('Failed to remove file'); }
        });
    });

    // ---- Extra details ----
    $('#attrAdd').click(() => {
        $('#attrList').append(`<div class="row g-2 mb-2 attr-row">
            <div class="col-md-4"><input type="text" class="form-control attr-label" placeholder="Label e.g. Cooking suggestion"></div>
            <div class="col-md-7"><textarea class="form-control attr-value" rows="1" placeholder="Detail…"></textarea></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm attr-del">✕</button></div>
        </div>`);
    });

    $(document).on('click', '.attr-del', function () { $(this).closest('.attr-row').remove(); });

    $('#attrSave').click(() => {
        const attributes = $('.attr-row').map((_, row) => ({
            label: $(row).find('.attr-label').val().trim(),
            value: $(row).find('.attr-value').val().trim(),
        })).get().filter(a => a.label && a.value);
        showLoader();
        $.post(`/admin/products/${PID}/attributes`, { attributes }, d => { hideLoader(); showSuccess(d.message); })
            .fail(xhr => { hideLoader(); showError(xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : 'Failed to save'); });
    });

    // ---- Variants ----
    let variantCache = [];

    function loadVariants() {
        $.get(`/admin/products/${PID}/variants`, list => {
            variantCache = list;
            $('#variantRows').html(list.map(v => `<tr>
                <td>${v.combination || '<span class="text-muted">Simple (no options)</span>'}</td>
                <td>${v.sku ?? '<span class="text-muted">—</span>'}</td>
                <td>£${Number(v.mrp).toFixed(2)}</td>
                <td>${v.offer_price ? '£' + Number(v.offer_price).toFixed(2) : '<span class="text-muted">—</span>'}</td>
                <td><strong>£${sellingOf(v).toFixed(2)}</strong></td>
                <td><div class="form-check form-switch" dir="ltr"><input type="checkbox" class="form-check-input toggle-stock" data-id="${v.id}" ${v.in_stock ? 'checked' : ''}></div></td>
                <td>${v.is_default ? '<span class="badge bg-success">Default</span>' : `<button class="btn btn-sm btn-outline-secondary" onclick="setDefault(${v.id})">Set</button>`}</td>
                <td><button class="btn btn-sm btn-outline-primary" onclick="editVariant(${v.id})">Edit</button>
                <button class="btn btn-sm btn-outline-danger" onclick="delVariant(${v.id})">Delete</button></td>
            </tr>`).join('') || '<tr><td colspan="8" class="text-muted">No variants yet.</td></tr>');
        });
    }

    function sellingOf(v) {
        const mrp = Number(v.mrp), offer = v.offer_price === null ? null : Number(v.offer_price);
        return (offer !== null && offer < mrp) ? offer : mrp;
    }

    loadVariants();

    $('#saveGroups').click(() => {
        const group_ids = $('.group-check:checked').map((_, el) => el.value).get();
        showLoader();
        $.post(`/admin/products/${PID}/variant-groups`, { group_ids }, d => { hideLoader(); showSuccess(d.message); location.reload(); })
            .fail(() => { hideLoader(); showError('Failed to save groups'); });
    });

    function selectedValueIds() {
        return $('.value-check:checked').map((_, el) => el.value).get();
    }

    $('#variantForm').submit(e => {
        e.preventDefault();
        const id = $('#variant_id').val();
        const fd = new FormData();
        fd.append('sku', $('#v_sku').val());
        fd.append('mrp', $('#v_mrp').val());
        fd.append('offer_price', $('#v_offer').val());
        fd.append('in_stock', $('#v_stock').is(':checked') ? 1 : 0);
        selectedValueIds().forEach(v => fd.append('value_ids[]', v));
        const img = $('#v_image')[0].files[0];
        if (img) fd.append('image', img);
        const url = id ? `/admin/product-variants/${id}` : `/admin/products/${PID}/variants`;
        showLoader();
        $.ajax({ url, type: 'POST', data: fd, contentType: false, processData: false,
            success: d => { hideLoader(); showSuccess(d.message); resetVariantForm(); loadVariants(); },
            error: xhr => { hideLoader(); showError(xhr.status === 422 ? (xhr.responseJSON?.message ?? Object.values(xhr.responseJSON.errors)[0][0]) : 'Error'); } });
    });

    function resetVariantForm() {
        $('#variant_id').val('');
        $('#variantForm')[0].reset();
        $('#v_stock').prop('checked', true);
        $('#variantFormTitle').text('Add variant');
        $('#variantSaveBtn').text('Add variant');
    }
    $('#variantResetBtn').click(resetVariantForm);

    window.editVariant = id => {
        const v = variantCache.find(x => x.id === id);
        if (!v) return;
        $('#variant_id').val(v.id);
        $('#v_sku').val(v.sku ?? '');
        $('#v_mrp').val(v.mrp);
        $('#v_offer').val(v.offer_price ?? '');
        $('#v_stock').prop('checked', !!v.in_stock);
        $('.value-check').prop('checked', false);
        (v.values || []).forEach(val => $(`.value-check[value="${val.id}"]`).prop('checked', true));
        $('#variantFormTitle').text('Edit variant' + (v.combination ? ' — ' + v.combination : ''));
        $('#variantSaveBtn').text('Update variant');
        pagetop();
    };

    window.delVariant = id => {
        if (!confirm('Delete this variant?')) return;
        $.ajax({ url: `/admin/product-variants/${id}`, type: 'DELETE', success: d => { showSuccess(d.message); loadVariants(); } });
    };

    window.setDefault = id => $.post(`/admin/product-variants/${id}/default`, d => { showSuccess(d.message); loadVariants(); });

    $(document).on('change', '.toggle-stock', function () {
        $.post("{{ route('product-variants.toggleStock') }}", { id: $(this).data('id') }, d => { showSuccess(d.message); loadVariants(); });
    });

    // Generate one £0 row per missing combination of the ticked values.
    $('#generateBtn').click(() => {
        const groups = [];
        $('#valueChecks [data-group]').each(function () {
            const ids = $(this).find('.value-check:checked').map((_, el) => el.value).get();
            if (ids.length) groups.push(ids);
        });
        if (!groups.length) { showError('Tick at least one option value first'); return; }
        const combos = groups.reduce((acc, ids) => acc.flatMap(a => ids.map(i => [...a, i])), [[]]);
        const existing = new Set(variantCache.map(v => (v.values || []).map(x => String(x.id)).sort().join(',')));
        const missing = combos.filter(c => !existing.has(c.map(String).sort().join(',')));
        if (!missing.length) { showSuccess('All combinations already exist'); return; }
        showLoader();
        const chain = missing.reduce((p, combo) => p.then(() => $.post(`/admin/products/${PID}/variants`,
            { mrp: 0, in_stock: 1, value_ids: combo })), Promise.resolve());
        chain.then(() => { hideLoader(); showSuccess(missing.length + ' variant(s) generated — set their prices'); loadVariants(); })
            .catch(() => { hideLoader(); showError('Generation stopped on an error'); loadVariants(); });
    });
});
</script>
@endsection
