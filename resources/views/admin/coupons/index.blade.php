@extends('admin.pages.master')
@section('title', 'Coupons')
@section('content')
<div class="container-fluid"><div class="row mb-3"><div class="col text-end"><button class="btn btn-primary" id="newBtn">Add Coupon</button></div></div></div>
<div class="container-fluid" id="formBox" style="display:none;"><div class="row justify-content-center"><div class="col-xl-8"><div class="card"><div class="card-header"><h4 id="cardTitle">Add Coupon</h4></div>
<div class="card-body"><form id="mainForm">@csrf<input type="hidden" id="codeid">
<div class="row">
<div class="col-md-4 mb-2"><label class="form-label">Code *</label><input class="form-control" id="code" placeholder="e.g. SAVE10" style="text-transform:uppercase;"></div>
<div class="col-md-4 mb-2"><label class="form-label">Type *</label><select class="form-control" id="type"><option value="percent">Percent %</option><option value="fixed">Fixed £</option></select></div>
<div class="col-md-4 mb-2"><label class="form-label">Value *</label><input type="number" step="0.01" min="0.01" class="form-control" id="value" placeholder="10"></div>
<div class="col-md-4 mb-2"><label class="form-label">Min order £</label><input type="number" step="0.01" min="0" class="form-control" id="min_order" placeholder="0"></div>
<div class="col-md-4 mb-2"><label class="form-label">Expires at</label><input type="datetime-local" class="form-control" id="expires_at"></div>
<div class="col-md-4 mb-2"><label class="form-label">Max uses (blank = unlimited)</label><input type="number" min="1" class="form-control" id="max_uses"></div>
<div class="col-md-4 mb-2"><label class="form-label">Max per person *</label><input type="number" min="1" class="form-control" id="max_per_user" value="1"></div>
<div class="col-md-4 mb-2"><label class="form-label">Status</label><select class="form-control" id="status"><option value="1">Active</option><option value="0">Disabled</option></select></div>
</div>
</form></div>
<div class="card-footer text-end"><button id="saveBtn" class="btn btn-primary" value="Create">Create</button> <button id="cancelBtn" class="btn btn-light">Cancel</button></div>
</div></div></div></div>
<div class="container-fluid"><div class="card"><div class="card-header"><h4>Coupons</h4></div>
<div class="card-body"><div class="table-responsive"><table id="couponTable" class="table table-bordered table-striped w-100"><thead><tr><th>Sl</th><th>Code</th><th>Value</th><th>Uses</th><th>Expiry</th><th>Status</th><th>Action</th></tr></thead></table></div></div></div></div>
@endsection
@section('script')
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    const t = $('#couponTable').DataTable({ processing: true, serverSide: true, ajax: "{{ route('coupons.index') }}",
        columns: [{ data: 'DT_RowIndex', orderable: false, searchable: false }, { data: 'code' }, { data: 'value' }, { data: 'uses', orderable: false, searchable: false }, { data: 'expiry' }, { data: 'status', orderable: false, searchable: false }, { data: 'action', orderable: false, searchable: false }] });
    const fields = ['code', 'type', 'value', 'min_order', 'expires_at', 'max_uses', 'max_per_user', 'status'];
    $('#newBtn').click(() => { $('#mainForm')[0].reset(); $('#codeid').val(''); $('#max_per_user').val(1); $('#saveBtn').val('Create').html('Create'); $('#formBox').show(300); $('#newBtn').hide(); });
    $('#cancelBtn').click(() => { $('#formBox').hide(); $('#newBtn').show(); });
    $('#saveBtn').click(function () {
        const create = $(this).val() === 'Create';
        const payload = { id: $('#codeid').val() };
        fields.forEach(f => payload[f] = $('#' + f).val());
        $.post(create ? "{{ route('coupons.store') }}" : "{{ route('coupons.update') }}", payload,
            d => { showSuccess(d.message); $('#formBox').hide(); $('#newBtn').show(); t.ajax.reload(null, false); })
            .fail(xhr => showError(xhr.status === 422 ? Object.values(xhr.responseJSON.errors)[0][0] : 'Error'));
    });
    $(document).on('click', '.editBtn', function () { $.get("{{ url('/admin/coupons') }}/" + $(this).data('id') + '/edit', d => {
        $('#codeid').val(d.id);
        fields.forEach(f => { let v = d[f]; if (f === 'expires_at' && v) v = v.replace(' ', 'T').slice(0, 16); $('#' + f).val(v ?? ''); });
        $('#saveBtn').val('Update').html('Update'); $('#formBox').show(300); $('#newBtn').hide(); pagetop();
    }); });
    $(document).on('change', '.toggle-status', function () { $.post("{{ route('coupons.toggleStatus') }}", { id: $(this).data('id') }, d => { showSuccess(d.message); t.ajax.reload(null, false); }); });
});
</script>
@endsection
