@extends('admin.pages.master')
@section('title', 'Category')
@section('content')

    <div class="container-fluid" id="newBtnSection">
        <div class="row mb-3">
            <div class="col text-end">
                <button type="button" class="btn btn-primary" id="newBtn">
                    {{ 'Add New Category' }}
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid" id="addThisFormContainer">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1" id="cardTitle">{{ 'Add New Category' }}</h4>
                    </div>
                    <div class="card-body">
                        <form id="createThisForm">
                            @csrf
                            <input type="hidden" id="codeid" name="codeid">

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">{{ 'Category Name' }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="">
                                </div>

                                <div class="col-md-6 d-none">
                                    <label class="form-label">{{ 'Parent Category' }}</label>
                                    <select class="form-control select2" id="parent_id" name="parent_id">
                                        <option value="">{{ 'Select Category' }}</option>
                                        @foreach ($parentCategories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-12 d-none">
                                    <label class="form-label">{{ 'Description' }}</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" placeholder=""></textarea>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">{{ 'Image' }}</label>
                                    <input type="file" class="form-control" id="image" accept="image/*"
                                        onchange="previewImage(event, '#preview-image')">
                                    <div id="current_image_box" style="display:none" class="mt-1 small"><span id="current_image_name"></span> <label class="ms-2"><input type="checkbox" name="remove_image" id="remove_image" value="1"> Remove current file</label></div>
                                    <img id="preview-image" src="#" alt="" class="img-thumbnail rounded mt-3"
                                        style="max-width: 300px; display: none;">
                                </div>

                                <div class="col-md-12 d-none">
                                    <label class="form-label">{{ 'Category Video URL' }} <small class="text-muted">(used on product details page, optional)</small></label>
                                    <input type="url" class="form-control" id="video_url" name="video_url" placeholder="https://...mp4">
                                </div>

                                <div class="col-12 d-none"><hr><h6 class="mb-0">SEO (for frontend meta tags)</h6></div>

                                <div class="col-md-12 d-none">
                                    <label class="form-label">{{ 'Meta Title' }}</label>
                                    <input type="text" class="form-control" id="meta_title" name="meta_title" maxlength="255">
                                </div>

                                <div class="col-md-12 d-none">
                                    <label class="form-label">{{ 'Meta Description' }}</label>
                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="2"></textarea>
                                </div>

                                <div class="col-md-6 d-none">
                                    <label class="form-label">{{ 'Meta Keywords' }}</label>
                                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" placeholder="comma, separated">
                                </div>

                                <div class="col-md-6 d-none">
                                    <label class="form-label">{{ 'Meta Image' }} <small class="text-muted">1200x630</small></label>
                                    <input type="file" class="form-control" id="meta_image" accept="image/*"
                                        onchange="previewImage(event, '#preview-meta-image')">
                                    <div id="current_meta_image_box" style="display:none" class="mt-1 small"><span id="current_meta_image_name"></span> <label class="ms-2"><input type="checkbox" name="remove_meta_image" id="remove_meta_image" value="1"> Remove current file</label></div>
                                    <img id="preview-meta-image" src="#" alt="" class="img-thumbnail rounded mt-3"
                                        style="max-width: 300px; display: none;">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" id="addBtn" class="btn btn-primary">
                            {{ 'Create' }}
                        </button>
                        <button type="button" id="FormCloseBtn" class="btn btn-light">
                            {{ 'Cancel' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid" id="contentContainer">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="categoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="parent-tab" data-bs-toggle="tab"
                            data-bs-target="#parent-categories" type="button" role="tab">
                            {{ 'All Categories' }}
                        </button>
                    </li>
                    <li class="nav-item d-none" role="presentation">
                        <button class="nav-link" id="child-tab" data-bs-toggle="tab" data-bs-target="#child-categories"
                            type="button" role="tab">
                            {{ 'Sub-Categories' }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-sort" role="tab" id="sortTab">
                            <i class="ri-sort-asc align-middle me-1"></i> Sort Categories
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body tab-content">
                <div class="tab-pane fade show active" id="parent-categories" role="tabpanel">
                    <div class="table-responsive">
                        <table id="parentCategoryTable" class="table table-bordered table-striped w-100">
                            <thead>
                                <tr>
                                    <th>{{ 'Serial' }}</th>
                                    <th>{{ 'Category Name' }}</th>
                                    <th>{{ 'Image' }}</th>
                                    <th>{{ 'Status' }}</th>
                                    <th>{{ 'Action' }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="child-categories" role="tabpanel">
                    <div class="table-responsive">
                        <table id="childCategoryTable" class="table table-bordered table-striped w-100">
                            <thead>
                                <tr>
                                    <th>{{ 'Serial' }}</th>
                                    <th>{{ 'Category Name' }}</th>
                                    <th>{{ 'Parent' }}</th>
                                    <th>{{ 'Image' }}</th>
                                    <th>{{ 'Status' }}</th>
                                    <th>{{ 'Action' }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <!-- Sort Categories Tab -->
                <div class="tab-pane fade" id="tab-sort" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="text-muted mb-0"><i class="ri-drag-move-2-line align-middle me-1"></i> Drag and drop categories to reorder them. Changes are saved automatically.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="refreshSortList">
                            <i class="ri-refresh-line align-middle me-1"></i> Refresh
                        </button>
                    </div>
                    <div id="sortableCategories" class="sortable-list" style="min-height:200px;">
                        <div class="text-center py-5 text-muted" id="sortLoading">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2">Click "Sort Categories" tab to load...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script>
        // Function to load parent categories via AJAX
        function loadParentCategories() {
            $.ajax({
                url: "{{ route('parent.categories') }}",
                method: "GET",
                success: function(response) {
                    $('#parent_id').empty().append('<option value="">{{ 'Select Category' }}</option>');

                    response.forEach(function(category) {
                        $('#parent_id').append('<option value="' + category.id + '">' + category.name +
                            '</option>');
                    });

                    $('#parent_id').trigger('change');
                },
                error: function(xhr) {
                    console.error('Failed to load parent categories');
                }
            });
        }

        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "{{ 'Select Category' }}",
                allowClear: true,
                width: '100%'
            });

            $('#parentCategoryTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('allcategory') }}?type=parent",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'image',
                        name: 'image',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $('#childCategoryTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('allcategory') }}?type=child",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'parent_category',
                        name: 'parent_category'
                    },
                    {
                        data: 'image',
                        name: 'image',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $(document).on('change', '.toggle-status', function() {
                var category_id = $(this).data('id');
                var status = $(this).prop('checked') ? 1 : 0;

                $.ajax({
                    url: '/admin/category-status',
                    method: "POST",
                    data: {
                        category_id: category_id,
                        status: status,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(d) {
                        reloadTable('#parentCategoryTable');
                        reloadTable('#childCategoryTable');
                        showSuccess(d.message);
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        showError("{{ 'Error updating status' }}");
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $("#addThisFormContainer").hide();
            $("#newBtn").click(function() {
                clearform();
                $("#newBtn").hide(100);
                $("#addThisFormContainer").show(300);
                loadParentCategories();

                $('#parent_id').select2({
                    placeholder: "{{ 'Select Category' }}",
                    allowClear: true,
                    width: '100%'
                });
            });

            $("#FormCloseBtn").click(function() {
                $("#addThisFormContainer").hide(200);
                $("#newBtn").show(100);
                clearform();
            });

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var url = "{{ URL::to('/admin/category') }}";
            var upurl = "{{ URL::to('/admin/category-update') }}";

            $("#addBtn").click(function() {
                if ($(this).val() == 'Create') {
                    var form_data = new FormData();
                    form_data.append("name", $("#name").val());
                    form_data.append("parent_id", $("#parent_id").val());
                    form_data.append("description", $("#description").val());
                    form_data.append("video_url", $("#video_url").val());
                    form_data.append("meta_title", $("#meta_title").val());
                    form_data.append("meta_description", $("#meta_description").val());
                    form_data.append("meta_keywords", $("#meta_keywords").val());

                    var featureImgInput = document.getElementById('image');
                    if (featureImgInput.files && featureImgInput.files[0]) {
                        form_data.append("image", featureImgInput.files[0]);
                    }
                    var metaImgInput = document.getElementById('meta_image');
                    if (metaImgInput.files && metaImgInput.files[0]) {
                        form_data.append("meta_image", metaImgInput.files[0]);
                    }

                    showLoader();
                    $.ajax({
                        url: url,
                        method: "POST",
                        contentType: false,
                        processData: false,
                        data: form_data,
                        success: function(d) {
                            showSuccess(d.message);
                            hideLoader();
                            $("#addThisFormContainer").slideUp(300);
                            setTimeout(() => {
                                $("#newBtn").show(200);
                            }, 300);
                            reloadTable('#parentCategoryTable');
                            reloadTable('#childCategoryTable');
                            // New rows go to the end (sort_order = max + 1): jump the
                            // relevant table to its last page so the new row is visible.
                            var targetTable = form_data.get('parent_id')
                                ? $('#childCategoryTable').DataTable()
                                : $('#parentCategoryTable').DataTable();
                            targetTable.page('last').draw(false);
                            clearform();
                            loadParentCategories();
                        },
                        error: function(xhr, status, error) {
                            hideLoader();
                            if (xhr.status === 422) {
                                let firstError = Object.values(xhr.responseJSON.errors)[0][0];
                                showError(firstError);
                            } else {
                                showError(xhr.responseJSON?.message ?? "{{ 'Error creating category' }}");
                            }
                            console.error(xhr.responseText);
                        }
                    });
                }

                if ($(this).val() == 'Update') {
                    var form_data = new FormData();
                    form_data.append("name", $("#name").val());
                    form_data.append("parent_id", $("#parent_id").val());
                    form_data.append("description", $("#description").val());
                    form_data.append("video_url", $("#video_url").val());
                    form_data.append("meta_title", $("#meta_title").val());
                    form_data.append("meta_description", $("#meta_description").val());
                    form_data.append("meta_keywords", $("#meta_keywords").val());

                    var featureImgInput = document.getElementById('image');
                    if (featureImgInput.files && featureImgInput.files[0]) {
                        form_data.append("image", featureImgInput.files[0]);
                    }
                    var metaImgInput = document.getElementById('meta_image');
                    if (metaImgInput.files && metaImgInput.files[0]) {
                        form_data.append("meta_image", metaImgInput.files[0]);
                    }
                    form_data.append("remove_image", $("#remove_image").is(":checked") ? 1 : 0);
                    form_data.append("remove_meta_image", $("#remove_meta_image").is(":checked") ? 1 : 0);

                    form_data.append("codeid", $("#codeid").val());

                    showLoader();

                    $.ajax({
                        url: upurl,
                        type: "POST",
                        dataType: 'json',
                        contentType: false,
                        processData: false,
                        data: form_data,
                        success: function(d) {
                            showSuccess(d.message);
                            $("#addThisFormContainer").slideUp(300);
                            setTimeout(() => {
                                $("#newBtn").show(200);
                            }, 300);
                            reloadTable('#parentCategoryTable');
                            reloadTable('#childCategoryTable');
                            clearform();
                            loadParentCategories();
                        },
                        error: function(xhr, status, error) {
                            if (xhr.status === 422) {
                                let firstError = Object.values(xhr.responseJSON.errors)[0][0];
                                showError(firstError);
                            } else {
                                showError(xhr.responseJSON?.message ?? "{{ 'Error updating category' }}");
                            }
                            console.error(xhr.responseText);
                        }
                    });
                }
            });

            $("#contentContainer").on('click', '#EditBtn', function() {
                $("#cardTitle").text("{{ 'Update Data' }}");
                codeid = $(this).attr('rid');
                info_url = url + '/' + codeid + '/edit';
                $.get(info_url, {}, function(d) {
                    populateForm(d);
                    pagetop();
                });
            });

            function populateForm(data) {
                $("#name").val(data.name);
                $("#description").val(data.description);
                $("#video_url").val(data.video_url);
                $("#meta_title").val(data.meta_title);
                $("#meta_description").val(data.meta_description);
                $("#meta_keywords").val(data.meta_keywords);
                $("#codeid").val(data.id);
                $("#addBtn").val('Update');
                $("#addBtn").html("{{ 'Update' }}");
                $("#addThisFormContainer").show(300);
                $("#newBtn").hide(100);

                loadParentCategories();

                setTimeout(function() {
                    if (data.parent_id) {
                        $('#parent_id').val(data.parent_id).trigger('change');
                    } else {
                        $('#parent_id').val(null).trigger('change');
                    }
                }, 300);

                var featureImagePreview = document.getElementById('preview-image');
                if (data.image) {
                    featureImagePreview.src = data.image;
                    featureImagePreview.style.display = 'block';
                    $('#current_image_name').html('Current: <a href="' + data.image + '" target="_blank">' + data.image.split('/').pop() + '</a>');
                    $('#current_image_box').show();
                    $('#remove_image').prop('checked', false);
                } else {
                    featureImagePreview.src = "#";
                    featureImagePreview.style.display = 'none';
                }

                var metaImagePreview = document.getElementById('preview-meta-image');
                if (data.meta_image) {
                    metaImagePreview.src = data.meta_image;
                    metaImagePreview.style.display = 'block';
                    $('#current_meta_image_name').html('Current: <a href="' + data.meta_image + '" target="_blank">' + data.meta_image.split('/').pop() + '</a>');
                    $('#current_meta_image_box').show();
                    $('#remove_meta_image').prop('checked', false);
                } else {
                    metaImagePreview.src = "#";
                    metaImagePreview.style.display = 'none';
                }
            }

            function clearform() {
                $('#createThisForm')[0].reset();
                $("#addBtn").val('Create');
                $("#addBtn").html("{{ 'Create' }}");
                $('#preview-image').attr('src', '#');
                $('#preview-image').hide();
                $('#preview-meta-image').attr('src', '#');
                $('#preview-meta-image').hide();
                $('#current_image_box,#current_meta_image_box').hide();
                $('#remove_image,#remove_meta_image').prop('checked', false);
                $("#cardTitle").text("{{ 'Add New Category' }}");

                $('#parent_id').val(null).trigger('change');
            }
        });
    </script>

    <script>
        // ===== SORTABLE CATEGORIES =====
        var sortableCatLoaded = false;

        function loadCategorySortList() {
            if (sortableCatLoaded) return;
            $.get("{{ route('category.sortList') }}", function(categories) {
                var html = '';
                if (categories.length === 0) {
                    html = '<div class="text-center py-5 text-muted"><i class="ri-inbox-line fs-1"></i><p class="mt-2">No categories found</p></div>';
                } else {
                    categories.forEach(function(c, i) {
                        var img = c.image ? '<img src="' + c.image + '" class="rounded" style="width:45px;height:45px;object-fit:cover;">' : '<div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:45px;height:45px;"><i class="ri-image-line text-muted"></i></div>';
                        var parentLabel = c.parent_id ? '<small class="text-muted">Subcategory</small>' : '<small class="text-muted">Parent</small>';
                        html += '<div class="sort-item" data-id="' + c.id + '">';
                        html += '  <div class="d-flex align-items-center gap-3">';
                        html += '    <span class="sort-handle text-muted"><i class="ri-drag-move-line fs-5"></i></span>';
                        html += '    ' + img;
                        html += '    <div class="flex-grow-1">';
                        html += '      <div class="fw-semibold">' + (c.name || '') + '</div>';
                        html += '      ' + parentLabel;
                        html += '    </div>';
                        html += '    <span class="badge bg-light text-dark sort-position">#' + (i + 1) + '</span>';
                        html += '  </div>';
                        html += '</div>';
                    });
                }
                $('#sortableCategories').html(html);
                sortableCatLoaded = true;
                initCategorySortable();
            });
        }

        function initCategorySortable() {
            $('#sortableCategories').sortable({
                handle: '.sort-handle',
                placeholder: 'sort-placeholder',
                tolerance: 'pointer',
                opacity: 0.8,
                cursor: 'grabbing',
                update: function() {
                    var ids = $('#sortableCategories').sortable('toArray', { attribute: 'data-id' });
                    $('#sortableCategories .sort-item').each(function(i) {
                        $(this).find('.sort-position').text('#' + (i + 1));
                    });
                    $.ajax({
                        url: "{{ route('category.sortUpdate') }}",
                        method: "POST",
                        data: {
                            ids: ids,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(d) {
                            showSuccess(d.message);
                            reloadTable('#parentCategoryTable');
                            reloadTable('#childCategoryTable');
                        },
                        error: function() {
                            showError('Failed to update sort order');
                        }
                    });
                }
            }).disableSelection();
        }

        // Load sort list when tab is clicked
        $('#sortTab').on('shown.bs.tab', function() {
            loadCategorySortList();
        });

        // Refresh button
        $('#refreshSortList').on('click', function() {
            sortableCatLoaded = false;
            $('#sortableCategories').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading categories...</p></div>');
            loadCategorySortList();
        });

        // Invalidate sort list when DataTable is reloaded
        $('#parentCategoryTable, #childCategoryTable').on('draw.dt', function() {
            sortableCatLoaded = false;
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