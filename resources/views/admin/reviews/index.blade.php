@extends('admin.pages.master')
@section('title', 'Product Reviews')

@section('content')

    <div class="container-fluid" id="contentContainer">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">All Product Reviews</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reviewTable" class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Sl</th>
                                <th>Product</th>
                                <th>Shopper</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reviewEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reviewId">
                    <p class="text-muted small mb-3" id="reviewContext"></p>
                    <div class="mb-3">
                        <label class="form-label">Rating <span class="text-danger">*</span></label>
                        <select class="form-control" id="reviewRating">
                            <option value="5">5 — Excellent</option>
                            <option value="4">4 — Good</option>
                            <option value="3">3 — Average</option>
                            <option value="2">2 — Poor</option>
                            <option value="1">1 — Terrible</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Headline</label>
                        <input type="text" class="form-control" id="reviewTitle" maxlength="120">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Review <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reviewBody" rows="4" maxlength="2000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="reviewSaveBtn"><i class="ri-save-line me-1"></i> Save</button>
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

            $('#reviewTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                ajax: '{{ route('reviews.index') }}',
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'product', name: 'product', orderable: false, searchable: false },
                    { data: 'user', name: 'user', orderable: false, searchable: false },
                    { data: 'rating', name: 'rating' },
                    { data: 'review', name: 'review', orderable: false, searchable: false },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $(document).on('change', '.toggle-status', function() {
                var id = $(this).data('id');
                $.post('{{ route('reviews.toggleStatus') }}', { id: id }, function(res) {
                    reloadTable('#reviewTable');
                    showSuccess(res.message);
                });
            });

            $(document).on('click', '.edit-btn', function() {
                var url = $(this).data('url');
                showLoader();
                $.get(url, function(res) {
                    hideLoader();
                    if (res.success) {
                        var d = res.data;
                        $('#reviewId').val(d.id);
                        $('#reviewContext').text(d.product + ' — by ' + d.user);
                        $('#reviewRating').val(d.rating);
                        $('#reviewTitle').val(d.title);
                        $('#reviewBody').val(d.body);
                        new bootstrap.Modal(document.getElementById('reviewEditModal')).show();
                    }
                });
            });

            $('#reviewSaveBtn').on('click', function() {
                showLoader();
                $.ajax({
                    url: '{{ route('reviews.update') }}',
                    method: 'POST',
                    data: {
                        id: $('#reviewId').val(),
                        rating: $('#reviewRating').val(),
                        title: $('#reviewTitle').val(),
                        body: $('#reviewBody').val()
                    },
                    success: function(res) {
                        hideLoader();
                        bootstrap.Modal.getInstance(document.getElementById('reviewEditModal')).hide();
                        reloadTable('#reviewTable');
                        showSuccess(res.message);
                    },
                    error: function(xhr) {
                        hideLoader();
                        if (xhr.status === 422) {
                            var first = Object.values(xhr.responseJSON.errors)[0][0];
                            showError(first);
                        } else {
                            showError(xhr.responseJSON?.message ?? 'Something went wrong.');
                        }
                    }
                });
            });
        });
    </script>
@endsection
