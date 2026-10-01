@extends('admin.pages.master')
@section('title', 'Sliders')

@section('content')

    <div class="container-fluid mb-3" id="newBtnSection">
        <button class="btn btn-primary" id="newBtn">
            <i class="ri-add-line me-1"></i> Add New Slider
        </button>
    </div>

    <div class="container-fluid" id="addThisFormContainer" style="display:none;">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1" id="cardTitle">Add New Slider</h4>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="sliderId">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Badge <small class="text-muted">(small top text, optional)</small></label>
                                <input type="text" class="form-control" id="badge" placeholder="e.g. Fresh picks · Everyday goodness">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Title <small class="text-muted">(optional)</small></label>
                                <input type="text" class="form-control" id="title" placeholder="e.g. Good food. Better days.">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Subtitle <small class="text-muted">(optional)</small></label>
                                <textarea class="form-control" id="subtitle" rows="2" placeholder="Short description under the title"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Primary Button Text</label>
                                <input type="text" class="form-control" id="btn_text" placeholder="e.g. Shop groceries">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Primary Button URL</label>
                                <input type="text" class="form-control" id="btn_url" placeholder="e.g. /shop">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Secondary Button Text <small class="text-muted">(optional)</small></label>
                                <input type="text" class="form-control" id="btn_text2" placeholder="e.g. Explore offers">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Secondary Button URL <small class="text-muted">(optional)</small></label>
                                <input type="text" class="form-control" id="btn_url2" placeholder="e.g. /about">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Slider Image <span class="text-danger">*</span>
                                <small class="text-muted">(Recommended: 1920px wide)</small>
                                </label>
                                <input type="file" class="form-control" id="image" accept="image/*" onchange="previewImage(event, '#imagePreview')">
                                <div id="current_image_box" style="display:none" class="mt-2"><button type="button" id="removeImageBtn" class="btn btn-sm btn-outline-danger">Remove image</button></div>
                                <img id="imagePreview" src="{{ asset('placeholder.webp') }}" class="img-thumbnail mt-2" style="max-width:300px; display:block;">
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
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#all-sliders" type="button" role="tab">
                            All Sliders
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-sort" role="tab" id="sortTab">
                            <i class="ri-sort-asc align-middle me-1"></i> Sort Sliders
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body tab-content">
                <div class="tab-pane fade show active" id="all-sliders" role="tabpanel">
                    <div class="table-responsive">
                        <table id="sliderTable" class="table table-bordered table-striped" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>Sl</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-sort" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="text-muted mb-0"><i class="ri-drag-move-2-line align-middle me-1"></i> Drag and drop sliders to reorder them. Changes are saved automatically.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="refreshSortList">
                            <i class="ri-refresh-line align-middle me-1"></i> Refresh
                        </button>
                    </div>
                    <div id="sortableSliders" class="sortable-list" style="min-height:200px;">
                        <div class="text-center py-5 text-muted">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2">Click "Sort Sliders" tab to load...</p>
                        </div>
                    </div>
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

            $('#sliderTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                ajax: '{{ route('slider.index') }}',
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'image', name: 'image', orderable: false, searchable: false },
                    { data: 'title', name: 'title', orderable: false, searchable: false },
                    { data: 'status', name: 'status', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ]
            });

            $('#newBtn').on('click', function() {
                clearForm();
                $('#cardTitle').text('Add New Slider');
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
                var id = $('#sliderId').val();
                var url = id ? '{{ route('slider.update') }}' : '{{ route('slider.store') }}';

                var formData = new FormData();
                formData.append('badge', $('#badge').val());
                formData.append('title', $('#title').val());
                formData.append('subtitle', $('#subtitle').val());
                formData.append('btn_text', $('#btn_text').val());
                formData.append('btn_url', $('#btn_url').val());
                formData.append('btn_text2', $('#btn_text2').val());
                formData.append('btn_url2', $('#btn_url2').val());
                if (id) formData.append('id', id);

                var imageFile = document.getElementById('image').files[0];
                if (imageFile) formData.append('image', imageFile);

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
                            reloadTable('#sliderTable');
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
                        $('#sliderId').val(d.id);
                        $('#badge').val(d.badge);
                        $('#title').val(d.title);
                        $('#subtitle').val(d.subtitle);
                        $('#btn_text').val(d.btn_text);
                        $('#btn_url').val(d.btn_url);
                        $('#btn_text2').val(d.btn_text2);
                        $('#btn_url2').val(d.btn_url2);

                        $('#imagePreview').attr('src', d.image ? d.image : '/placeholder.webp');
                        if (d.image && d.image !== 'placeholder.webp') {
                            $('#current_image_box').show();
                            $('#removeImageBtn').data('id', d.id);
                        } else {
                            $('#current_image_box').hide();
                        }
                        $('#cardTitle').text('Edit Slider');
                        $('#addThisFormContainer').slideDown(300);
                        $('#newBtn').hide();
                        pageTop();
                    }
                });
            });

            $(document).on('change', '.toggle-status', function() {
                var id = $(this).data('id');
                $.post('{{ route('slider.toggleStatus') }}', { id: id }, function(res) {
                    reloadTable('#sliderTable');
                    showSuccess(res.message);
                });
            });

            function clearForm() {
                $('#sliderId').val('');
                $('#badge').val('');
                $('#title').val('');
                $('#subtitle').val('');
                $('#btn_text').val('');
                $('#btn_url').val('');
                $('#btn_text2').val('');
                $('#btn_url2').val('');
                $('#image').val('');
                $('#imagePreview').attr('src', '/placeholder.webp');
                $('#current_image_box').hide();
            }

            $('#removeImageBtn').on('click', function () {
                var id = $(this).data('id') || $('#sliderId').val();
                if (!id) return;
                showLoader();
                $.ajax({
                    url: "{{ url('/admin/sliders') }}/" + id + "/image",
                    method: 'DELETE',
                    success: function (res) {
                        hideLoader();
                        showSuccess(res.message);
                        $('#imagePreview').attr('src', '/placeholder.webp');
                        $('#current_image_box').hide();
                        $('#image').val('');
                        reloadTable('#sliderTable');
                    },
                    error: function () { hideLoader(); showError('Failed to remove image'); }
                });
            });
        });

        function previewImage(event, previewId) {
            var reader = new FileReader();
            reader.onload = function() {
                $(previewId).attr('src', reader.result);
            }
            reader.readAsDataURL(event.target.files[0]);
        }

        // ===== SORTABLE SLIDERS =====
        var sortableSlidersLoaded = false;

        function loadSliderSortList() {
            if (sortableSlidersLoaded) return;
            $.get("{{ route('slider.sortList') }}", function(sliders) {
                var html = '';
                if (sliders.length === 0) {
                    html = '<div class="text-center py-5 text-muted"><i class="ri-inbox-line fs-1"></i><p class="mt-2">No sliders found</p></div>';
                } else {
                    sliders.forEach(function(s, i) {
                        html += '<div class="sort-item" data-id="' + s.id + '">';
                        html += '  <div class="d-flex align-items-center gap-3">';
                        html += '    <span class="sort-handle text-muted"><i class="ri-drag-move-line fs-5"></i></span>';
                        html += '    <img src="' + s.image + '" class="rounded" style="width:80px;height:45px;object-fit:cover;">';
                        html += '    <div class="flex-grow-1">';
                        html += '      <div class="fw-semibold">' + (s.title || s.badge || 'Untitled slide') + '</div>';
                        html += '      <small class="text-muted">' + (s.badge || '') + '</small>';
                        html += '    </div>';
                        html += '    <span class="badge bg-light text-dark sort-position">#' + (i + 1) + '</span>';
                        html += '  </div>';
                        html += '</div>';
                    });
                }
                $('#sortableSliders').html(html);
                sortableSlidersLoaded = true;
                initSliderSortable();
            });
        }

        function initSliderSortable() {
            $('#sortableSliders').sortable({
                handle: '.sort-handle',
                placeholder: 'sort-placeholder',
                tolerance: 'pointer',
                opacity: 0.8,
                cursor: 'grabbing',
                update: function() {
                    var ids = $('#sortableSliders').sortable('toArray', { attribute: 'data-id' });
                    $('#sortableSliders .sort-item').each(function(i) {
                        $(this).find('.sort-position').text('#' + (i + 1));
                    });
                    $.ajax({
                        url: "{{ route('slider.sortUpdate') }}",
                        method: "POST",
                        data: {
                            ids: ids,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(d) {
                            showSuccess(d.message);
                            reloadTable('#sliderTable');
                        },
                        error: function() {
                            showError('Failed to update sort order');
                        }
                    });
                }
            }).disableSelection();
        }

        $('#sortTab').on('shown.bs.tab', function() {
            loadSliderSortList();
        });

        $('#refreshSortList').on('click', function() {
            sortableSlidersLoaded = false;
            $('#sortableSliders').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading sliders...</p></div>');
            loadSliderSortList();
        });

        $('#sliderTable').on('draw.dt', function() {
            sortableSlidersLoaded = false;
        });
    </script>

    <style>
        .sort-item {
            padding: 12px 16px;
            margin-bottom: 8px;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .sort-item:hover {
            border-color: #dee2e6;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }
        .sort-handle {
            cursor: grab;
            display: flex;
            align-items: center;
            padding: 4px;
        }
        .sort-handle:active {
            cursor: grabbing;
        }
        .sort-placeholder {
            padding: 12px 16px;
            margin-bottom: 8px;
            background: #e8f4fd;
            border: 2px dashed #0d6efd;
            border-radius: 10px;
            min-height: 70px;
        }
        .sort-position {
            font-size: 0.8rem;
            font-weight: 600;
            min-width: 36px;
            text-align: center;
        }
        .ui-sortable-helper {
            box-shadow: 0 8px 24px rgba(0,0,0,.15);
            transform: rotate(1deg);
        }
    </style>
@endsection
