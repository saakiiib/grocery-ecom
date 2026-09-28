@extends('admin.pages.master')
@section('title', 'Galleries')
@section('content')
<div class="container-fluid"><div class="row mb-3 align-items-center">
<div class="col-md-4"><select class="form-control" id="filterCat"><option value="">All Categories</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
<div class="col text-end"><button class="btn btn-primary" id="newBtn">Add Image</button></div></div></div>
<div class="container-fluid" id="formBox" style="display:none;"><div class="row justify-content-center"><div class="col-xl-8"><div class="card"><div class="card-header"><h4 id="cardTitle">Add Image</h4></div>
<div class="card-body"><form id="mainForm" enctype="multipart/form-data">@csrf<input type="hidden" id="codeid">
<div class="row g-2">
<div class="col-md-6"><label class="form-label">Category</label><select class="form-control select2" id="gallery_category_id" name="gallery_category_id"><option value="">Select</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Caption</label><input class="form-control" id="caption" name="caption"></div>
<div class="col-12"><label class="form-label">Image *</label><input type="file" class="form-control" id="image" name="image" accept="image/*" onchange="previewImage(event, '#preview-img')"><div id="current_image_box" style="display:none" class="mt-1 small"><span id="current_image_name"></span> <label class="ms-2"><input type="checkbox" name="remove_image" id="remove_image" value="1"> Remove current file</label></div><img id="preview-img" src="#" class="img-thumbnail mt-2" style="display:none;max-width:250px;"></div>
</div></form></div>
<div class="card-footer text-end"><button id="saveBtn" class="btn btn-primary" value="Create">Create</button> <button id="cancelBtn" class="btn btn-light">Cancel</button></div>
</div></div></div></div>
<div class="container-fluid"><div class="card"><div class="card-header"><h4>Gallery Images</h4></div>
<div class="card-body"><div class="table-responsive"><table id="galleryTable" class="table table-bordered table-striped w-100"><thead><tr><th>Sl</th><th>Image</th><th>Category</th><th>Caption</th><th>Status</th><th>Action</th></tr></thead></table></div></div></div></div>
@endsection
@section('script')
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $('.select2').select2({ width: '100%' });
    const t = $('#galleryTable').DataTable({ processing: true, serverSide: true,
        ajax: { url: "{{ route('galleries.index') }}", data: d => d.gallery_category_id = $('#filterCat').val() },
        columns: [{ data: 'DT_RowIndex', orderable: false, searchable: false }, { data: 'image', orderable: false, searchable: false }, { data: 'category' }, { data: 'caption' }, { data: 'status', orderable: false, searchable: false }, { data: 'action', orderable: false, searchable: false }] });
    $('#filterCat').change(() => t.ajax.reload());
    $('#newBtn').click(() => { $('#mainForm')[0].reset(); $('#gallery_category_id').val('').trigger('change'); $('#codeid').val(''); $('#preview-img').hide(); $('#current_image_box').hide(); $('#remove_image').prop('checked', false); $('#saveBtn').val('Create').html('Create'); $('#formBox').show(300); $('#newBtn').hide(); });
    $('#cancelBtn').click(() => { $('#formBox').hide(); $('#newBtn').show(); });
    $('#saveBtn').click(function () {
        const create = $(this).val() === 'Create';
        const fd = new FormData(document.getElementById('mainForm'));
        if (!create) fd.append('id', $('#codeid').val());
        $.ajax({ url: create ? "{{ route('galleries.store') }}" : "{{ route('galleries.update') }}", type: 'POST', data: fd, contentType: false, processData: false,
            success: d => { showSuccess(d.message); $('#formBox').hide(); $('#newBtn').show(); t.ajax.reload(null, false); },
            error: xhr => showError(xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : 'Error') });
    });
    $(document).on('click', '.editBtn', function () { $.get("{{ url('/admin/galleries') }}/" + $(this).data('id') + '/edit', d => { $('#codeid').val(d.id); $('#gallery_category_id').val(d.gallery_category_id).trigger('change'); $('#caption').val(d.caption); if (d.preview) $('#preview-img').attr('src', d.preview).show(); if (d.image) { const src = d.preview || d.image; $('#current_image_name').html('Current: <a href="' + src + '" target="_blank">' + String(d.image).split('/').pop() + '</a>'); $('#current_image_box').show(); $('#remove_image').prop('checked', false); } else { $('#current_image_box').hide(); } $('#saveBtn').val('Update').html('Update'); $('#formBox').show(300); $('#newBtn').hide(); pagetop(); }); });
    $(document).on('change', '.toggle-status', function () { $.post("{{ route('galleries.toggleStatus') }}", { id: $(this).data('id') }, d => { showSuccess(d.message); t.ajax.reload(null, false); }); });
});
</script>
@endsection
