@extends('admin.pages.master')
@section('title', 'Testimonials')

@section('content')

    <div class="container-fluid mb-3" id="newBtnSection">
        <button class="btn btn-primary" id="newBtn">
            <i class="ri-add-line me-1"></i> Add New Testimonial
        </button>
    </div>

    <div class="container-fluid" id="addThisFormContainer" style="display:none;">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1" id="cardTitle">Add New Testimonial</h4>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="testimonialId">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" placeholder="Enter name">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Designation</label>
                                <input type="text" class="form-control" id="designation" placeholder="e.g. Customer, Manager">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Review <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="review" rows="3" placeholder="Enter review"></textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Image</label>
                                <input type="file" class="form-control" id="image" accept="image/*" onchange="previewImage(event, '#imagePreview')">
                                <div id="current_image_box" style="display:none" class="mt-1 small"><span id="current_image_name"></span> <label class="ms-2"><input type="checkbox" name="remove_image" id="remove_image" value="1"> Remove current file</label></div>
                                <img id="imagePreview" src="{{ asset('placeholder.webp') }}" class="img-thumbnail mt-2" style="width:100px;height:100px;object-fit:cover;border-radius:50%; display:block;">
                            </div>

                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button class="btn btn-primary" id="saveBtn"><i class="ri-save-line me-1"></i> Save</button>
                        <button class="btn btn-light ms-1" id="cancelBtn">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid" id="contentContainer">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">All Testimonials</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="testimonialTable" class="table table-bordered table-striped" style="width: 100%">
                        <thead>
                            <tr>
                                <th>Sl</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Designation</th>
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

@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });

            $('#testimonialTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                ajax: '{{ route('testimonial.index') }}',
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'image', name: 'image', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'designation', name: 'designation', defaultContent: '-' },
                    { data: 'review', name: 'review' },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#newBtn').on('click', function() {
                clearForm();
                $('#cardTitle').text('Add New Testimonial');
                $('#addThisFormContainer').slideDown(300);
                $('#newBtn').hide();
                pageTop();
            });

            $('#cancelBtn').on('click', function() {
                $('#addThisFormContainer').slideUp(200);
                $('#newBtn').show();
                clearForm();
            });

            $('#saveBtn').on('click', function() {
                var id = $('#testimonialId').val();
                var url = id ? '{{ route('testimonial.update') }}' : '{{ route('testimonial.store') }}';

                var formData = new FormData();
                formData.append('name', $('#name').val());
                formData.append('designation', $('#designation').val());
                formData.append('review', $('#review').val());
                if (id) formData.append('id', id);

                var imageFile = document.getElementById('image').files[0];
                if (imageFile) formData.append('image', imageFile);
                formData.append('remove_image', $('#remove_image').is(':checked') ? 1 : 0);

                showLoader();

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        if (res.success) {
                            showSuccess(res.message);
                            $('#addThisFormContainer').slideUp(200);
                            $('#newBtn').show();
                            clearForm();
                            reloadTable('#testimonialTable');
                        }
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

            $(document).on('click', '.edit-btn', function() {
                var url = $(this).data('url');
                showLoader();
                $.get(url, function(res) {
                    hideLoader();
                    if (res.success) {
                        var d = res.data;
                        $('#testimonialId').val(d.id);
                        $('#name').val(d.name);
                        $('#designation').val(d.designation);
                        $('#review').val(d.review);

                        $('#imagePreview').attr('src', d.image ? d.image : '/placeholder.webp');
                        if (d.image && d.image !== 'placeholder.webp') {
                            $('#current_image_name').html('Current: <a href="' + d.image + '" target="_blank">' + String(d.image).split('/').pop() + '</a>');
                            $('#current_image_box').show();
                            $('#remove_image').prop('checked', false);
                        } else {
                            $('#current_image_box').hide();
                        }
                        $('#cardTitle').text('Edit Testimonial');
                        $('#addThisFormContainer').slideDown(300);
                        $('#newBtn').hide();
                        pageTop();
                    }
                });
            });

            $(document).on('change', '.toggle-status', function() {
                var id = $(this).data('id');
                $.post('{{ route('testimonial.toggleStatus') }}', { id: id }, function(res) {
                    reloadTable('#testimonialTable');
                    showSuccess(res.message);
                });
            });

            function clearForm() {
                $('#testimonialId').val('');
                $('#name').val('');
                $('#designation').val('');
                $('#review').val('');
                $('#image').val('');
                $('#imagePreview').attr('src', '/placeholder.webp');
                $('#current_image_box').hide();
                $('#remove_image').prop('checked', false);
            }
        });

        function previewImage(event, previewId) {
            var reader = new FileReader();
            reader.onload = function() {
                $(previewId).attr('src', reader.result);
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
@endsection
