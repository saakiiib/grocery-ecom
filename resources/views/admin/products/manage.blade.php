@extends('admin.pages.master')
@section('title', 'Manage Product — ' . $product->name)
@section('content')

<div class="container-fluid">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <a href="{{ route('products.index') }}" class="btn btn-light btn-sm">← Back to Products</a>
            <h4 class="mt-2 mb-0">{{ $product->name }} <small class="text-muted">{{ $product->model_code }}</small></h4>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button">1. Basic + SEO</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-images" type="button">2. Images</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-matspecs" type="button">3. Materials & Specs</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-options" type="button">4. Options</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tech" type="button">5. Tech Specs</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button">6. Documents</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-zones" type="button">7. Floor Zones</button></li>
            </ul>
        </div>
        <div class="card-body tab-content">
            <div class="tab-pane fade show active" id="tab-basic">
                <form id="basicForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="codeid" value="{{ $product->id }}">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Name *</label><input type="text" class="form-control" name="name" value="{{ $product->name }}"></div>
                        <div class="col-md-3"><label class="form-label">Model Code *</label><input type="text" class="form-control" name="model_code" value="{{ $product->model_code }}"></div>
                        <div class="col-md-3"><label class="form-label">Category</label>
                            <select class="form-control select2" name="category_id">
                                <option value="">Select</option>
                                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected($product->category_id == $c->id)>{{ $c->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label">Tagline</label><input type="text" class="form-control" name="tagline" value="{{ $product->tagline }}"></div>
                        <div class="col-12"><label class="form-label">Description</label><textarea class="form-control summernote" name="description" rows="4">{{ $product->description }}</textarea></div>
                        <div class="col-md-3"><label class="form-label">Price (£) <small class="text-muted">empty = on request</small></label><input type="number" step="0.01" min="0" class="form-control" name="base_price" value="{{ $product->base_price }}"></div>
                        <div class="col-md-3"><label class="form-label">Lead Time</label><input type="text" class="form-control" name="lead_time" value="{{ $product->lead_time }}"></div>
                        <div class="col-md-3"><label class="form-label">Dimensions</label><input type="text" class="form-control" name="dimensions" value="{{ $product->dimensions }}"></div>
                        <div class="col-md-3"><label class="form-label">Warranty</label><input type="text" class="form-control" name="warranty" value="{{ $product->warranty }}"></div>
                        <div class="col-md-6"><label class="form-label">Hero Image</label><input type="file" class="form-control" name="hero_image" accept="image/*">
                            @if ($product->hero_image)<img src="{{ $product->hero_image }}" class="img-thumbnail mt-2" style="max-width:200px;">
                            <div id="current_hero_image_box" class="mt-1 small"><span id="current_hero_image_name">Current: <a href="{{ $product->hero_image }}" target="_blank">{{ basename($product->hero_image) }}</a></span> <label class="ms-2"><input type="checkbox" name="remove_hero_image" id="remove_hero_image" value="1"> Remove current file</label></div>@endif</div>
                        <div class="col-md-6"><label class="form-label">Video URL <small class="text-muted">empty = category video</small></label><input type="url" class="form-control" name="video_url" value="{{ $product->video_url }}">
                            <div class="form-check mt-2"><input type="checkbox" class="form-check-input" name="show_3d" value="1" @checked($product->show_3d)><label class="form-check-label">Show 3D viewer tab</label></div></div>
                        <div class="col-12"><hr><h6>SEO (frontend meta tags)</h6></div>
                        <div class="col-md-6"><label class="form-label">Meta Title</label><input type="text" class="form-control" name="meta_title" value="{{ $product->meta_title }}"></div>
                        <div class="col-md-6"><label class="form-label">Meta Keywords</label><input type="text" class="form-control" name="meta_keywords" value="{{ $product->meta_keywords }}"></div>
                        <div class="col-md-8"><label class="form-label">Meta Description</label><textarea class="form-control" name="meta_description" rows="2">{{ $product->meta_description }}</textarea></div>
                        <div class="col-md-4"><label class="form-label">Meta Image</label><input type="file" class="form-control" name="meta_image" accept="image/*">
                            @if ($product->meta_image)<img src="{{ $product->meta_image }}" class="img-thumbnail mt-2" style="max-width:150px;">
                            <div id="current_meta_image_box" class="mt-1 small"><span id="current_meta_image_name">Current: <a href="{{ $product->meta_image }}" target="_blank">{{ basename($product->meta_image) }}</a></span> <label class="ms-2"><input type="checkbox" name="remove_meta_image" id="remove_meta_image" value="1"> Remove current file</label></div>@endif</div>
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

            <div class="tab-pane fade" id="tab-matspecs">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Materials (tag list)</h6>
                        <form id="matForm" class="d-flex gap-2 mb-2"><input type="text" class="form-control" id="matName" placeholder="e.g. Solid Smoked European Oak" required><button class="btn btn-primary">Add</button></form>
                        <ul id="matList" class="list-group"></ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Spec bullets (4 recommended)</h6>
                        <form id="specForm" class="d-flex gap-2 mb-2"><input type="text" class="form-control" id="specPoint" placeholder="Spec bullet..." required><button class="btn btn-primary">Add</button></form>
                        <ul id="specList" class="list-group"></ul>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-options">
                <form id="optForm" class="row g-2 mb-3">
                    <div class="col-md-2"><select class="form-control" id="optGroup"><option value="config">Configuration</option><option value="finish">Finish</option><option value="glazing">Glazing</option><option value="upgrade">Upgrade</option></select></div>
                    <div class="col-md-3"><input type="text" class="form-control" id="optName" placeholder="Option name *" required></div>
                    <div class="col-md-3"><input type="text" class="form-control" id="optSub" placeholder="Subtitle"></div>
                    <div class="col-md-2"><input type="number" step="0.01" min="0" class="form-control" id="optPrice" placeholder="+£ (empty=includ.)"></div>
                    <div class="col-md-1"><input type="color" class="form-control" id="optSwatch" value="#5A4636" title="Finish colour"></div>
                    <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
                    <div class="col-12"><div class="form-check"><input type="checkbox" class="form-check-input" id="optDefault"><label class="form-check-label" for="optDefault">Default selected</label></div></div>
                </form>
                <div class="mb-2">
                    <button class="btn btn-sm btn-dark optFilter" data-g="">All</button>
                    <button class="btn btn-sm btn-outline-dark optFilter" data-g="config">Configuration</button>
                    <button class="btn btn-sm btn-outline-dark optFilter" data-g="finish">Finish</button>
                    <button class="btn btn-sm btn-outline-dark optFilter" data-g="glazing">Glazing</button>
                    <button class="btn btn-sm btn-outline-dark optFilter" data-g="upgrade">Upgrade</button>
                </div>
                <div id="optList" class="list-group"></div>
            </div>

            <div class="tab-pane fade" id="tab-tech">
                <form id="techForm" class="row g-2 mb-3">
                    <div class="col-md-4"><input type="text" class="form-control" id="techLabel" placeholder="Label *" required></div>
                    <div class="col-md-5"><input type="text" class="form-control" id="techValue" placeholder="Value *" required></div>
                    <div class="col-md-2"><div class="form-check mt-2"><input type="checkbox" class="form-check-input" id="techHi"><label class="form-check-label" for="techHi">Highlight</label></div></div>
                    <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
                </form>
                <div id="techList" class="list-group"></div>
            </div>

            <div class="tab-pane fade" id="tab-docs">
                <form id="docForm" class="row g-2 mb-3">
                    <div class="col-md-5"><input type="text" class="form-control" id="docTitle" placeholder="Title e.g. Architectural Lookbook (PDF) *" required></div>
                    <div class="col-md-5"><input type="file" class="form-control" id="docFile" required></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">Upload</button></div>
                </form>
                <div id="docList" class="list-group"></div>
            </div>

            <div class="tab-pane fade" id="tab-zones">
                <p class="text-muted small">Zones for this product's Floor Plan tab. Empty = the global Floor Zones set is shown instead.</p>
                <form id="zoneForm" class="row g-2 mb-3">
                    <div class="col-md-3"><input type="text" class="form-control" id="zoneName" placeholder="Zone name * e.g. Cinema Room" required></div>
                    <div class="col-md-3"><input type="text" class="form-control" id="zoneDims" placeholder="Dims e.g. 4.2 m × 3.6 m"></div>
                    <div class="col-md-4"><input type="text" class="form-control" id="zoneDesc" placeholder="Short description"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">Add Zone</button></div>
                </form>
                <div id="zoneList" class="list-group"></div>
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
        if (!$('[name=show_3d]').is(':checked')) fd.set('show_3d', 0);
        showLoader();
        $.ajax({ url: "{{ route('products.update') }}", type: 'POST', data: fd, contentType: false, processData: false,
            success: d => { hideLoader(); showSuccess(d.message); },
            error: xhr => { hideLoader(); showError(xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : 'Error'); } });
    });

    const reload = { img: loadImg, mat: loadMat, spec: loadSpec, opt: loadOpt, tech: loadTech, doc: loadDoc, zone: loadZone };
    let optGroup = '';
    let zoneCache = {};
    $('.optFilter').click(function () { optGroup = $(this).data('g'); loadOpt(); });

    function loadImg() { $.get(`/admin/products/${PID}/images`, list => { $('#imgList').html(list.map(i => `<div class="col-md-3"><div class="card"><img src="${i.preview}" class="card-img-top"><div class="card-body p-2"><input class="form-control form-control-sm mb-1" value="${i.caption ?? ''}" onchange="updImg(${i.id},this.value)"><button class="btn btn-sm btn-danger" onclick="delImg(${i.id})">Delete</button></div></div></div>`).join('') || '<p class="text-muted">No images yet. First image acts as gallery backup to hero.</p>'); }); }
    function loadMat() { $.get(`/admin/products/${PID}/materials`, list => { $('#matList').html(list.map(m => `<li class="list-group-item d-flex justify-content-between">${m.name}<span><button class="btn btn-sm btn-link" onclick="editMat(${m.id},'${m.name.replace(/'/g, "\\'")}')">Edit</button><button class="btn btn-sm btn-link text-danger" onclick="delMat(${m.id})">Delete</button></span></li>`).join('')); }); }
    function loadSpec() { $.get(`/admin/products/${PID}/specs`, list => { $('#specList').html(list.map(s => `<li class="list-group-item d-flex justify-content-between"><span>${s.point}</span><span><button class="btn btn-sm btn-link" onclick="editSpec(${s.id})">Edit</button><button class="btn btn-sm btn-link text-danger" onclick="delSpec(${s.id})">Delete</button></span></li>`).join('')); }); }
    function loadOpt() { $.get(`/admin/products/${PID}/options`, { group: optGroup }, list => { $('#optList').html(list.map(o => `<div class="list-group-item d-flex justify-content-between align-items-center"><div><strong>[${o.group}]</strong> ${o.name} <small class="text-muted">${o.subtitle ?? ''}</small> <span class="badge bg-light text-dark">${o.price_delta ? '+£' + Number(o.price_delta).toLocaleString() : 'Included'}</span> ${o.is_default ? '<span class="badge bg-success">Default</span>' : ''} ${o.swatch_color ? `<span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:${o.swatch_color};border:1px solid #ccc;"></span>` : ''}</div><span><button class="btn btn-sm btn-link" onclick="editOpt(${o.id},'${o.name.replace(/'/g, "\\'")}')">Edit</button><button class="btn btn-sm btn-link text-danger" onclick="delOpt(${o.id})">Delete</button></span></div>`).join('') || '<p class="text-muted">No options in this group yet.</p>'); }); }
    function loadTech() { $.get(`/admin/products/${PID}/tech-specs`, list => { $('#techList').html(list.map(t => `<div class="list-group-item d-flex justify-content-between"><div><strong>${t.label}:</strong> ${t.value} ${t.highlight ? '<span class="badge bg-warning">★</span>' : ''}</div><span><button class="btn btn-sm btn-link" onclick="editTech(${t.id},'${t.label.replace(/'/g, "\\'")}',\`${(t.value || '').replace(/`/g, '')}\`)">Edit</button><button class="btn btn-sm btn-link text-danger" onclick="delTech(${t.id})">Delete</button></span></div>`).join('')); }); }
    function loadDoc() { $.get(`/admin/products/${PID}/documents`, list => { $('#docList').html(list.map(d => `<div class="list-group-item d-flex justify-content-between"><div><strong>${d.title}</strong> <a href="${d.url}" target="_blank" class="ms-2">Open</a></div><button class="btn btn-sm btn-link text-danger" onclick="delDoc(${d.id})">Delete</button></div>`).join('') || '<p class="text-muted">Max 3 recommended: Lookbook / Manual / Spec Sheet.</p>'); }); }
    function loadZone() { $.get(`/admin/products/${PID}/floor-zones`, list => { list.forEach(z => { zoneCache[z.id] = { name: z.name, dims: z.dims, desc: z.desc }; }); $('#zoneList').html(list.map(z => `<div class="list-group-item"><div class="row g-2 align-items-center"><div class="col-md-3"><input class="form-control form-control-sm" value="${escAttr(z.name)}" onchange="updZone(${z.id},'name',this.value)"></div><div class="col-md-2"><input class="form-control form-control-sm" value="${escAttr(z.dims)}" placeholder="Dims" onchange="updZone(${z.id},'dims',this.value)"></div><div class="col-md-5"><input class="form-control form-control-sm" value="${escAttr(z.desc)}" placeholder="Description" onchange="updZone(${z.id},'desc',this.value)"></div><div class="col-md-2 text-end"><div class="form-check form-switch d-inline-block me-2"><input type="checkbox" class="form-check-input" ${z.status ? 'checked' : ''} onchange="toggleZone(${z.id})"></div><button class="btn btn-sm btn-link text-danger" onclick="delZone(${z.id})">Delete</button></div></div></div>`).join('') || '<p class="text-muted">No zones for this product yet — the global Floor Zones set is shown on its details page.</p>'); }); }
    function escAttr(v) { return String(v ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;'); }

    Object.values(reload).forEach(fn => fn());

    $('#imgForm').submit(e => { e.preventDefault(); const fd = new FormData(); fd.append('image', $('#imgFile')[0].files[0]); fd.append('caption', $('#imgCaption').val()); $.ajax({ url: `/admin/products/${PID}/images`, type: 'POST', data: fd, contentType: false, processData: false, success: d => { showSuccess(d.message); $('#imgForm')[0].reset(); loadImg(); }, error: xhr => showError(xhr.responseJSON?.message ?? 'Error') }); });
    $('#matForm').submit(e => { e.preventDefault(); $.post(`/admin/products/${PID}/materials`, { name: $('#matName').val() }, d => { showSuccess(d.message); $('#matName').val(''); loadMat(); }).fail(xhr => showError('Error')); });
    $('#specForm').submit(e => { e.preventDefault(); $.post(`/admin/products/${PID}/specs`, { point: $('#specPoint').val() }, d => { showSuccess(d.message); $('#specPoint').val(''); loadSpec(); }).fail(() => showError('Error')); });
    $('#optForm').submit(e => { e.preventDefault(); $.post(`/admin/products/${PID}/options`, { group: $('#optGroup').val(), name: $('#optName').val(), subtitle: $('#optSub').val(), price_delta: $('#optPrice').val(), swatch_color: $('#optSwatch').val(), is_default: $('#optDefault').is(':checked') ? 1 : 0 }, d => { showSuccess(d.message); $('#optForm')[0].reset(); loadOpt(); }).fail(xhr => showError(xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : 'Error')); });
    $('#techForm').submit(e => { e.preventDefault(); $.post(`/admin/products/${PID}/tech-specs`, { label: $('#techLabel').val(), value: $('#techValue').val(), highlight: $('#techHi').is(':checked') ? 1 : 0 }, d => { showSuccess(d.message); $('#techForm')[0].reset(); loadTech(); }).fail(() => showError('Error')); });
    $('#docForm').submit(e => { e.preventDefault(); const fd = new FormData(); fd.append('title', $('#docTitle').val()); fd.append('file', $('#docFile')[0].files[0]); $.ajax({ url: `/admin/products/${PID}/documents`, type: 'POST', data: fd, contentType: false, processData: false, success: d => { showSuccess(d.message); $('#docForm')[0].reset(); loadDoc(); }, error: () => showError('Error') }); });
    $('#zoneForm').submit(e => { e.preventDefault(); $.post(`/admin/products/${PID}/floor-zones`, { name: $('#zoneName').val(), dims: $('#zoneDims').val(), desc: $('#zoneDesc').val() }, d => { showSuccess(d.message); $('#zoneForm')[0].reset(); loadZone(); }).fail(xhr => showError(xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : 'Error')); });

    window.delImg = id => $.ajax({ url: `/admin/product-images/${id}`, type: 'DELETE', success: d => { showSuccess(d.message); loadImg(); } });
    window.updImg = (id, caption) => $.post(`/admin/product-images/${id}`, { caption }, () => loadImg());
    window.delMat = id => $.ajax({ url: `/admin/product-materials/${id}`, type: 'DELETE', success: () => loadMat() });
    window.editMat = (id, old) => { const v = prompt('Material name:', old); if (v) $.post(`/admin/product-materials/${id}`, { name: v }, () => loadMat()); };
    window.delSpec = id => $.ajax({ url: `/admin/product-specs/${id}`, type: 'DELETE', success: () => loadSpec() });
    window.editSpec = id => { const v = prompt('Spec point:'); if (v) $.post(`/admin/product-specs/${id}`, { point: v }, () => loadSpec()); };
    window.delOpt = id => $.ajax({ url: `/admin/product-options/${id}`, type: 'DELETE', success: () => loadOpt() });
    window.editOpt = (id, old) => { const v = prompt('Option name:', old); if (v) $.post(`/admin/product-options/${id}`, { name: v }, () => loadOpt()); };
    window.delTech = id => $.ajax({ url: `/admin/product-tech-specs/${id}`, type: 'DELETE', success: () => loadTech() });
    window.editTech = (id, l, v) => { const nl = prompt('Label:', l); if (!nl) return; const nv = prompt('Value:', v); if (nv !== null) $.post(`/admin/product-tech-specs/${id}`, { label: nl, value: nv }, () => loadTech()); };
    window.delDoc = id => $.ajax({ url: `/admin/product-documents/${id}`, type: 'DELETE', success: () => loadDoc() });
    window.delZone = id => $.ajax({ url: `/admin/product-floor-zones/${id}`, type: 'DELETE', success: d => { showSuccess(d.message); loadZone(); } });
    window.updZone = (id, field, value) => { const z = Object.assign({}, zoneCache[id], { [field]: value }); $.post(`/admin/product-floor-zones/${id}`, { name: z.name, dims: z.dims, desc: z.desc }, () => loadZone()).fail(() => { showError('Error'); loadZone(); }); };
    window.toggleZone = id => $.post(`/admin/product-floor-zones/${id}/toggle-status`, {}, () => loadZone());
});
</script>
@endsection
