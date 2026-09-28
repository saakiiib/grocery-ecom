@extends('admin.pages.master')
@section('title', 'Contacts')

@section('content')

    <div class="container-fluid" id="contentContainer">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">All Contacts</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="contactTable" class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Sl</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Read</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contact Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width:120px">Name</th>
                            <td id="viewName"></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td id="viewEmail"></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td id="viewPhone"></td>
                        </tr>
                        <tr>
                            <th>Subject</th>
                            <td id="viewSubject"></td>
                        </tr>
                        <tr>
                            <th>Message</th>
                            <td id="viewMessage"></td>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <td id="viewDate"></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td id="viewStatus"></td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });

            $('#contactTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                ajax: '{{ route('admin.contacts.index') }}',
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'email', name: 'email', defaultContent: '-' },
                    { data: 'phone', name: 'phone', defaultContent: '-' },
                    { data: 'subject', name: 'subject' },
                    { data: 'message', name: 'message' },
                    { data: 'date', name: 'created_at' },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $(document).on('click', '.view-btn', function() {
                var url = $(this).data('url');
                showLoader();
                $.get(url, function(res) {
                    hideLoader();
                    if (res.success) {
                        var d = res.data;
                        $('#viewName').text(d.name);
                        $('#viewEmail').text(d.email || '-');
                        $('#viewPhone').text(d.phone || '-');
                        $('#viewSubject').text(d.subject);
                        $('#viewMessage').text(d.message);
                        $('#viewDate').text(new Date(d.created_at).toLocaleString());
                        $('#viewStatus').html(d.status
                            ? '<span class="badge bg-success">Read</span>'
                            : '<span class="badge bg-warning">Unread</span>');
                        $('#viewModal').modal('show');
                    }
                }).fail(function() {
                    hideLoader();
                    showError('Failed to load contact details.');
                });
            });

            $(document).on('change', '.toggle-status', function() {
                var id = $(this).data('id');
                $.post('{{ route('admin.contacts.toggleStatus') }}', { id: id }, function(res) {
                    reloadTable('#contactTable');
                    showSuccess(res.message);
                });
            });
        });
    </script>
@endsection
