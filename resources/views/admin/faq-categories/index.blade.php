@extends('admin.pages.master')
@section('title', 'FAQ Categories')
@section('content')
<div class="container-fluid"><div class="row mb-3"><div class="col text-end"><button class="btn btn-primary" id="newBtn">Add Category</button></div></div></div>
<div class="container-fluid" id="formBox" style="display:none;"><div class="row justify-content-center"><div class="col-xl-6"><div class="card"><div class="card-header"><h4 id="cardTitle">Add Category</h4></div>
<div class="card-body"><form id="mainForm">@csrf<input type="hidden" id="codeid">
<div class="mb-2"><label class="form-label">Name *</label><input class="form-control" id="name" placeholder="e.g. Lead Times"></div>
</form></div>
<div class="card-footer text-end"><button id="saveBtn" class="btn btn-primary" value="Create">Create</button> <button id="cancelBtn" class="btn btn-light">Cancel</button></div>
</div></div></div></div>
<div class="container-fluid"><div class="card"><div class="card-header"><h4>FAQ Categories</h4></div>
<div class="card-body"><div class="table-responsive"><table id="catTable" class="table table-bordered table-striped w-100"><thead><tr><th>Sl</th><th>Name</th><th>Slug</th><th>FAQs</th><th>Status</th><th>Action</th></tr></thead></table></div></div></div></div>
@endsection
@section('script')
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    const t = $('#catTable').DataTable({ processing: true, serverSide: true, ajax: "{{ route('faq-categories.index') }}",
        columns: [{ data: 'DT_RowIndex', orderable: false, searchable: false }, { data: 'name' }, { data: 'slug' }, { data: 'count' }, { data: 'status', orderable: false, searchable: false }, { data: 'action', orderable: false, searchable: false }] });
    $('#newBtn').click(() => { $('#mainForm')[0].reset(); $('#codeid').val(''); $('#saveBtn').val('Create').html('Create'); $('#formBox').show(300); $('#newBtn').hide(); });
    $('#cancelBtn').click(() => { $('#formBox').hide(); $('#newBtn').show(); });
    $('#saveBtn').click(function () {
        const create = $(this).val() === 'Create';
        $.post(create ? "{{ route('faq-categories.store') }}" : "{{ route('faq-categories.update') }}", { id: $('#codeid').val(), name: $('#name').val() },
            d => { showSuccess(d.message); $('#formBox').hide(); $('#newBtn').show(); t.ajax.reload(null, false); })
            .fail(xhr => showError(xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : 'Error'));
    });
    $(document).on('click', '.editBtn', function () { $.get("{{ url('/admin/faq-categories') }}/" + $(this).data('id') + '/edit', d => { $('#codeid').val(d.id); $('#name').val(d.name); $('#saveBtn').val('Update').html('Update'); $('#formBox').show(300); $('#newBtn').hide(); pagetop(); }); });
    $(document).on('change', '.toggle-status', function () { $.post("{{ route('faq-categories.toggleStatus') }}", { id: $(this).data('id') }, d => { showSuccess(d.message); t.ajax.reload(null, false); }); });
});
</script>
@endsection
