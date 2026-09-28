@extends('admin.pages.master')
@section('title', 'Enquiries')
@section('content')
<div class="container-fluid"><div class="card"><div class="card-header"><h4>Enquiries — unified inbox (contact + custom-build + spec-pack)</h4></div>
<div class="card-body"><div class="table-responsive"><table id="enquiryTable" class="table table-bordered table-striped w-100"><thead><tr><th>Sl</th><th>Name</th><th>Email</th><th>Phone</th><th>Product</th><th>Guide Price</th><th>Source</th><th>Date</th><th>Read</th><th>Action</th></tr></thead></table></div></div></div></div>
<div class="modal fade" id="viewModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Enquiry Details</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><table class="table table-bordered">
<tr><th style="width:150px">Name</th><td id="vName"></td></tr>
<tr><th>Email</th><td id="vEmail"></td></tr>
<tr><th>Phone</th><td id="vPhone"></td></tr>
<tr><th>Postcode</th><td id="vPostcode"></td></tr>
<tr><th>Topic</th><td id="vTopic"></td></tr>
<tr><th>Product</th><td id="vProduct"></td></tr>
<tr><th>Config</th><td id="vConfig" style="white-space:pre-wrap"></td></tr>
<tr><th>Guide Price</th><td id="vPrice"></td></tr>
<tr><th>Message</th><td id="vMessage" style="white-space:pre-wrap"></td></tr>
<tr><th>Source</th><td id="vSource"></td></tr>
</table></div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
</div></div></div>
@endsection
@section('script')
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    const t = $('#enquiryTable').DataTable({ processing: true, serverSide: true, ajax: "{{ route('enquiries.index') }}",
        columns: [{ data: 'DT_RowIndex', orderable: false, searchable: false }, { data: 'name' }, { data: 'email' }, { data: 'phone' }, { data: 'product' }, { data: 'price' }, { data: 'source_page' }, { data: 'date' }, { data: 'status', orderable: false, searchable: false }, { data: 'action', orderable: false, searchable: false }] });
    $(document).on('click', '.viewBtn', function () { $.get("{{ url('/admin/enquiries') }}/" + $(this).data('id'), r => { const d = r.data;
        $('#vName').text(d.name); $('#vEmail').text(d.email); $('#vPhone').text(d.phone); $('#vPostcode').text(d.postcode);
        $('#vTopic').text(d.topic); $('#vProduct').text(d.product ? d.product.name + ' (' + d.product.model_code + ')' : '-');
        $('#vConfig').text(d.config_summary ?? '-'); $('#vPrice').text(d.guide_price ? '£' + Number(d.guide_price).toLocaleString() : '-');
        $('#vMessage').text(d.message ?? '-'); $('#vSource').text(d.source_page ?? '-');
        new bootstrap.Modal('#viewModal').show(); }); });
    $(document).on('change', '.toggle-status', function () { $.post("{{ route('enquiries.toggleStatus') }}", { id: $(this).data('id') }, d => { showSuccess(d.message); t.ajax.reload(null, false); }); });
});
</script>
@endsection
