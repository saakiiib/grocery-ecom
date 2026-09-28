@extends('admin.pages.master')
@section('title', 'FAQs')
@section('content')
<div class="container-fluid"><div class="row mb-3 align-items-center">
<div class="col-md-4"><select class="form-control" id="filterCat"><option value="">All Categories</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
<div class="col text-end"><button class="btn btn-primary" id="newBtn">Add FAQ</button></div></div></div>
<div class="container-fluid" id="formBox" style="display:none;"><div class="row justify-content-center"><div class="col-xl-8"><div class="card"><div class="card-header"><h4 id="cardTitle">Add FAQ</h4></div>
<div class="card-body"><form id="mainForm">@csrf<input type="hidden" id="codeid">
<div class="row g-2">
<div class="col-md-6"><label class="form-label">Category</label><select class="form-control select2" id="faq_category_id">@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Badge</label><input class="form-control" id="badge" placeholder="e.g. Lead Times"></div>
<div class="col-12"><label class="form-label">Question *</label><input class="form-control" id="question"></div>
<div class="col-12"><label class="form-label">Answer *</label><textarea class="form-control summernote" id="answer" rows="3"></textarea></div>
</div></form></div>
<div class="card-footer text-end"><button id="saveBtn" class="btn btn-primary" value="Create">Create</button> <button id="cancelBtn" class="btn btn-light">Cancel</button></div>
</div></div></div></div>
<div class="container-fluid"><div class="card"><div class="card-header"><h4>FAQs</h4></div>
<div class="card-body"><div class="table-responsive"><table id="faqTable" class="table table-bordered table-striped w-100"><thead><tr><th>Sl</th><th>Category</th><th>Question</th><th>Status</th><th>Action</th></tr></thead></table></div></div></div></div>
@endsection
@section('script')
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $('.select2').select2({ width: '100%' });
    $('.summernote').summernote({ height: 120 });
    const t = $('#faqTable').DataTable({ processing: true, serverSide: true,
        ajax: { url: "{{ route('faqs.index') }}", data: d => d.faq_category_id = $('#filterCat').val() },
        columns: [{ data: 'DT_RowIndex', orderable: false, searchable: false }, { data: 'category' }, { data: 'question' }, { data: 'status', orderable: false, searchable: false }, { data: 'action', orderable: false, searchable: false }] });
    $('#filterCat').change(() => t.ajax.reload());
    $('#newBtn').click(() => { $('#mainForm')[0].reset(); $('#answer').summernote('code', ''); $('#codeid').val(''); $('#saveBtn').val('Create').html('Create'); $('#formBox').show(300); $('#newBtn').hide(); });
    $('#cancelBtn').click(() => { $('#formBox').hide(); $('#newBtn').show(); });
    $('#saveBtn').click(function () {
        const create = $(this).val() === 'Create';
        $.post(create ? "{{ route('faqs.store') }}" : "{{ route('faqs.update') }}",
            { id: $('#codeid').val(), faq_category_id: $('#faq_category_id').val(), badge: $('#badge').val(), question: $('#question').val(), answer: $('#answer').summernote('code') },
            d => { showSuccess(d.message); $('#formBox').hide(); $('#newBtn').show(); t.ajax.reload(null, false); })
            .fail(xhr => showError(xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : 'Error'));
    });
    $(document).on('click', '.editBtn', function () { $.get("{{ url('/admin/faqs') }}/" + $(this).data('id') + '/edit', d => { $('#codeid').val(d.id); $('#faq_category_id').val(d.faq_category_id).trigger('change'); $('#badge').val(d.badge); $('#question').val(d.question); $('#answer').summernote('code', d.answer); $('#saveBtn').val('Update').html('Update'); $('#formBox').show(300); $('#newBtn').hide(); pagetop(); }); });
    $(document).on('change', '.toggle-status', function () { $.post("{{ route('faqs.toggleStatus') }}", { id: $(this).data('id') }, d => { showSuccess(d.message); t.ajax.reload(null, false); }); });
});
</script>
@endsection
