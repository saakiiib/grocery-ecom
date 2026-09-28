@extends('admin.pages.master')
@section('title', 'Downloads')
@section('content')
<div class="container-fluid"><div class="row mb-3"><div class="col text-end"><button class="btn btn-primary" id="newBtn">Add File</button></div></div></div>
<div class="container-fluid" id="formBox" style="display:none;"><div class="row justify-content-center"><div class="col-xl-8"><div class="card"><div class="card-header"><h4 id="cardTitle">Add File</h4></div>
<div class="card-body"><form id="mainForm" enctype="multipart/form-data">@csrf<input type="hidden" id="codeid">
<div class="row g-2">
<div class="col-md-6"><label class="form-label">Title *</label><input class="form-control" id="title" name="title"></div>
<div class="col-md-6"><label class="form-label">Ref</label><input class="form-control" id="ref" name="ref" placeholder="HS-KTC-01/SLN"></div>
<div class="col-md-4"><label class="form-label">Format *</label><select class="form-control select2" id="format" name="format"><option>PDF Spec</option><option>CAD Drawing</option><option>BIM/Revit</option><option>Installation Manual</option><option>Care Guide</option></select></div>
<div class="col-md-4"><label class="form-label">Revision</label><input class="form-control" id="rev" name="rev" placeholder="Q1 2025"></div>
<div class="col-md-4"><label class="form-label">Product (optional)</label><select class="form-control select2" id="product_id" name="product_id"><option value="">None</option>@foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label">File <small class="text-muted">pdf/cad/bim/zip max 50MB — required when creating</small></label><input type="file" class="form-control" id="file" name="file"><div id="currentFileBox" style="display:none" class="mt-1 small"><span id="currentFileName"></span> <label class="ms-2"><input type="checkbox" name="remove_file" id="remove_file" value="1"> Remove current file</label></div></div>
</div></form></div>
<div class="card-footer text-end"><button id="saveBtn" class="btn btn-primary" value="Create">Create</button> <button id="cancelBtn" class="btn btn-light">Cancel</button></div>
</div></div></div></div>
<div class="container-fluid"><div class="card"><div class="card-header"><h4>Downloads Library</h4></div>
<div class="card-body"><div class="table-responsive"><table id="downloadTable" class="table table-bordered table-striped w-100"><thead><tr><th>Sl</th><th>Title</th><th>Ref</th><th>Format</th><th>Size</th><th>Product</th><th>Status</th><th>Action</th></tr></thead></table></div></div></div></div>
@endsection
@section('script')
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $('.select2').select2({ width: '100%' });
    const t = $('#downloadTable').DataTable({ processing: true, serverSide: true, ajax: "{{ route('downloads.index') }}",
        columns: [{ data: 'DT_RowIndex', orderable: false, searchable: false }, { data: 'title' }, { data: 'ref' }, { data: 'format' }, { data: 'size' }, { data: 'product' }, { data: 'status', orderable: false, searchable: false }, { data: 'action', orderable: false, searchable: false }] });
    $('#newBtn').click(() => { $('#mainForm')[0].reset(); $('#format').val('').trigger('change'); $('#product_id').val('').trigger('change'); $('#remove_file').prop('checked', false); $('#currentFileBox').hide(); $('#codeid').val(''); $('#saveBtn').val('Create').html('Create'); $('#formBox').show(300); $('#newBtn').hide(); pagetop(); });
    $('#cancelBtn').click(() => { $('#formBox').hide(); $('#newBtn').show(); });
    $('#saveBtn').click(function () {
        const create = $(this).val() === 'Create';
        const fd = new FormData(document.getElementById('mainForm'));
        if (!create) fd.append('id', $('#codeid').val());
        $.ajax({ url: create ? "{{ route('downloads.store') }}" : "{{ route('downloads.update') }}", type: 'POST', data: fd, contentType: false, processData: false,
            success: d => { showSuccess(d.message); $('#formBox').hide(); $('#newBtn').show(); t.ajax.reload(null, false); },
            error: xhr => showError(xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : (xhr.responseJSON?.message ?? 'Error')) });
    });
    $(document).on('click', '.editBtn', function () { $.get("{{ url('/admin/downloads') }}/" + $(this).data('id') + '/edit').done(d => { try { $('#codeid').val(d.id); $('#title').val(d.title); $('#ref').val(d.ref); $('#format').val(d.format).trigger('change'); $('#rev').val(d.rev); $('#product_id').val(d.product_id).trigger('change'); $('#remove_file').prop('checked', false); if (d.file) { $('#currentFileName').html('<a href="' + d.file + '" target="_blank">Current file</a>'); $('#currentFileBox').show(); } else { $('#currentFileBox').hide(); } $('#saveBtn').val('Update').html('Update'); } catch (e) { console.error(e); } $('#formBox').show(); $('#newBtn').hide(); pagetop(); }).fail(xhr => showError(xhr.responseJSON?.message ?? 'Could not load record')); });
    $(document).on('change', '.toggle-status', function () { $.post("{{ route('downloads.toggleStatus') }}", { id: $(this).data('id') }, d => { showSuccess(d.message); t.ajax.reload(null, false); }); });
});
</script>
@endsection
