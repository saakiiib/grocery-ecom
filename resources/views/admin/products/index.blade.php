@extends('admin.pages.master')
@section('title', 'Products')
@section('content')

<div class="container-fluid" id="newBtnSection">
    <div class="row mb-3 align-items-center">
        <div class="col-md-4">
            <select class="form-control select2" id="filterCategory">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col text-end">
            <a href="{{ route('products.export') }}" class="btn btn-outline-success me-2"><i class="ri-download-line align-middle me-1"></i> Export Excel</a>
            <a href="{{ route('products.imageTemplate') }}" class="btn btn-outline-success me-2"><i class="ri-image-line align-middle me-1"></i> Image Template</a>
            <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#importModal"><i class="ri-upload-line align-middle me-1"></i> Import Excel</button>
            <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#sortModal" id="sortBtn"><i class="ri-sort-asc align-middle me-1"></i> Sort</button>
            <button type="button" class="btn btn-primary" id="newBtn">Add New Product</button>
        </div>
    </div>
</div>

<div class="container-fluid" id="addThisFormContainer" style="display:none;">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="card">
                <div class="card-header d-flex">
                    <h4 class="card-title mb-0 flex-grow-1" id="cardTitle">Add New Product</h4>
                </div>
                <div class="card-body">
                    <form id="createThisForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="codeid" name="codeid">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label">Category</label>
                                <select class="form-control select2" id="category_id" name="category_id">
                                    <option value="">Select</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Card subtitle <small class="text-muted">one line under the name on cards</small></label>
                                <input type="text" class="form-control" id="tagline" name="tagline" maxlength="150">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control summernote" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="col-md-12">
                                <div class="alert alert-info py-2 mb-0">Prices live on variants — after creating, open <strong>Manage Details → Variants</strong> to set SKUs and prices.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hero Image</label>
                                <input type="file" class="form-control" id="hero_image" name="hero_image" accept="image/*" onchange="previewImage(event, '#preview-hero')">
                                <div id="current_hero_image_box" style="display:none" class="mt-2"><button type="button" id="removeHeroBtn" class="btn btn-sm btn-outline-danger">Remove image</button></div>
                                <img id="preview-hero" src="#" class="img-thumbnail mt-2" style="display:none;max-width:250px;">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1">
                                    <label class="form-check-label" for="is_featured">Featured product</label>
                                </div>
                            </div>
                            <div class="col-12"><hr><h6>SEO (frontend meta tags)</h6></div>
                            <div class="col-md-6">
                                <label class="form-label">Meta Title</label>
                                <input type="text" class="form-control" id="meta_title" name="meta_title">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Meta Keywords</label>
                                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Meta Description</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="2"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Meta Image <small class="text-muted">1200x630</small></label>
                                <input type="file" class="form-control" id="meta_image" name="meta_image" accept="image/*" onchange="previewImage(event, '#preview-meta')">
                                <div id="current_meta_image_box" style="display:none" class="mt-2"><button type="button" id="removeMetaBtn" class="btn btn-sm btn-outline-danger">Remove image</button></div>
                                <img id="preview-meta" src="#" class="img-thumbnail mt-2" style="display:none;max-width:200px;">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-end">
                    <button type="button" id="addBtn" class="btn btn-primary" value="Create">Create</button>
                    <button type="button" id="FormCloseBtn" class="btn btn-light">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid" id="contentContainer">
    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">All Products</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="productTable" class="table table-bordered table-striped w-100">
                    <thead><tr><th>Sl</th><th>Image</th><th>Name</th><th>Default SKU</th><th>Category</th><th>Price</th><th>Featured</th><th>Status</th><th>Action</th></tr></thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('products.importPreview') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Import products from Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Upload an exported file (edited or not). You'll get a preview with counts and errors before anything is saved. New categories and option values are created automatically. Optionally attach a ZIP of photos (see Image Template).</p>
                <div class="mb-3"><label class="form-label">Excel file *</label><input type="file" class="form-control" name="file" accept=".xlsx,.xls,.csv" required></div>
                <div><label class="form-label">Photos ZIP <small class="text-muted">optional</small></label><input type="file" class="form-control" name="images" accept=".zip"></div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Upload &amp; preview</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="sortModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sort Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted"><i class="ri-drag-move-2-line align-middle me-1"></i> Drag and drop to reorder. Changes save automatically.</p>
                <div id="sortableProducts" class="sortable-list" style="min-height:150px;"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<style>
.sortable-list .sort-item{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:8px 12px;margin-bottom:8px}
.sortable-list .sort-item img{width:48px;height:36px;object-fit:cover;border-radius:6px}
.sortable-list .sort-handle{cursor:move;color:#9aa0a6}
.ui-sortable-helper{box-shadow:0 6px 18px rgba(0,0,0,.12)}
</style>
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $('.select2').select2({ width: '100%' });
    $('.summernote').summernote({ height: 120 });

    const table = $('#productTable').DataTable({
        processing: true, serverSide: true,
        ajax: { url: "{{ route('products.index') }}", data: d => { d.category_id = $('#filterCategory').val(); } },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'image', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'sku', name: 'sku', orderable: false, searchable: false },
            { data: 'category', name: 'category' },
            { data: 'price', orderable: false, searchable: false },
            { data: 'featured', orderable: false, searchable: false },
            { data: 'status', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false },
        ]
    });
    $('#filterCategory').on('change', () => table.ajax.reload());

    $('#newBtn').click(() => { resetForm(); $('#newBtn').hide(); $('#addThisFormContainer').show(300); });
    $('#FormCloseBtn').click(() => { $('#addThisFormContainer').hide(200); $('#newBtn').show(100); resetForm(); });

    function resetForm() {
        $('#createThisForm')[0].reset();
        $('#codeid').val('');
        $('#addBtn').val('Create').html('Create');
        $('#cardTitle').text('Add New Product');
        $('#preview-hero,#preview-meta').hide();
        $('#current_hero_image_box,#current_meta_image_box').hide();
        $('.summernote').summernote('code', '');
    }

    $('#addBtn').click(function () {
        const mode = $(this).val();
        const url = mode === 'Create' ? "{{ route('products.store') }}" : "{{ route('products.update') }}";
        const fd = new FormData(document.getElementById('createThisForm'));
        fd.set('description', $('#description').summernote('code'));
        fd.set('is_featured', $('#is_featured').is(':checked') ? 1 : 0);
        if (mode === 'Update') fd.append('codeid', $('#codeid').val());
        showLoader();
        $.ajax({
            url, type: 'POST', data: fd, contentType: false, processData: false,
            success: d => { hideLoader(); showSuccess(d.message); $('#addThisFormContainer').hide(); $('#newBtn').show(); table.ajax.reload(null, false); resetForm(); },
            error: xhr => { hideLoader(); showError(xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : (xhr.responseJSON?.message ?? 'Error')); }
        });
    });

    $(document).on('click', '.editBtn', function () {
        $.get("{{ url('/admin/products') }}/" + $(this).data('id') + "/edit", d => {
            resetForm();
            $('#codeid').val(d.id);
            $('#name').val(d.name);
            $('#category_id').val(d.category_id).trigger('change');
            $('#tagline').val(d.tagline); $('#description').summernote('code', d.description || '');
            $('#is_featured').prop('checked', !!d.is_featured);
            $('#meta_title').val(d.meta_title); $('#meta_keywords').val(d.meta_keywords); $('#meta_description').val(d.meta_description);
            if (d.hero_image) { $('#preview-hero').attr('src', d.hero_image).show(); $('#current_hero_image_box').show(); $('#removeHeroBtn').data('id', d.id); }
            if (d.meta_image) { $('#preview-meta').attr('src', d.meta_image).show(); $('#current_meta_image_box').show(); $('#removeMetaBtn').data('id', d.id); }
            $('#addBtn').val('Update').html('Update');
            $('#cardTitle').text('Quick Edit Product');
            $('#addThisFormContainer').show(300); $('#newBtn').hide();
            pagetop();
        });
    });

    function removeProductFile(id, field, onDone) {
        if (!id) return;
        showLoader();
        $.ajax({
            url: "{{ url('/admin/products') }}/" + id + "/file",
            type: 'DELETE',
            data: { field: field },
            success: d => { hideLoader(); showSuccess(d.message); onDone(); table.ajax.reload(null, false); },
            error: () => { hideLoader(); showError('Failed to remove file'); }
        });
    }

    $('#removeHeroBtn').click(function () {
        const id = $(this).data('id') || $('#codeid').val();
        removeProductFile(id, 'hero_image', () => { $('#preview-hero').hide(); $('#current_hero_image_box').hide(); $('#hero_image').val(''); });
    });
    $('#removeMetaBtn').click(function () {
        const id = $(this).data('id') || $('#codeid').val();
        removeProductFile(id, 'meta_image', () => { $('#preview-meta').hide(); $('#current_meta_image_box').hide(); $('#meta_image').val(''); });
    });

    $(document).on('change', '.toggle-status', function () {
        $.post("{{ route('products.toggleStatus') }}", { id: $(this).data('id') }, d => { showSuccess(d.message); table.ajax.reload(null, false); });
    });
    $(document).on('change', '.toggle-featured', function () {
        $.post("{{ route('products.toggleFeatured') }}", { id: $(this).data('id') }, d => { showSuccess(d.message); table.ajax.reload(null, false); });
    });

    let sortLoaded = false;
    function loadSortList() {
        $.get("{{ route('products.sortList') }}", list => {
            $('#sortableProducts').html(list.map(p => `<div class="sort-item" data-id="${p.id}"><span class="sort-handle"><i class="ri-drag-move-line fs-5"></i></span>${p.image ? `<img src="${p.image}">` : ''}<span><strong>${p.name}</strong><br><small class="text-muted">${p.sku ?? ''}</small></span></div>`).join('') || '<p class="text-muted">No products yet.</p>');
            sortLoaded = true;
            $('#sortableProducts').sortable({
                handle: '.sort-handle',
                update: () => {
                    const ids = $('#sortableProducts').sortable('toArray', { attribute: 'data-id' });
                    $.post("{{ route('products.sortUpdate') }}", { ids }, d => { showSuccess(d.message); table.ajax.reload(null, false); });
                }
            });
        });
    }
    $('#sortModal').on('show.bs.modal', () => { if (!sortLoaded) loadSortList(); });
});
</script>
@endsection
