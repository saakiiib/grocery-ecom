@extends('admin.pages.master')
@section('title', 'Option Groups')
@section('content')

    <div class="container-fluid" id="newBtnSection">
        <div class="row mb-3">
            <div class="col text-end">
                <button type="button" class="btn btn-primary" id="newBtn">
                    {{ 'Add New Option Group' }}
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid" id="addThisFormContainer">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1" id="cardTitle">{{ 'Add New Option Group' }}</h4>
                    </div>
                    <div class="card-body">
                        <form id="createThisForm">
                            @csrf
                            <input type="hidden" id="codeid" name="codeid">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ 'Group Name' }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Pack Size">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">{{ 'Display Type' }} <span class="text-danger">*</span></label>
                                    <select class="form-control" id="type" name="type">
                                        <option value="buttons">{{ 'Buttons' }}</option>
                                        <option value="dropdown">{{ 'Dropdown' }}</option>
                                    </select>
                                </div>

                                <div class="col-md-12">
                                    <p class="text-muted mb-0"><small>Values (e.g. 500g, 1kg) are added on the next screen via <strong>Manage Values</strong>. Groups appear as Excel columns and on the product page.</small></p>
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
                <ul class="nav nav-tabs card-header-tabs" id="groupTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="groups-tab" data-bs-toggle="tab"
                            data-bs-target="#all-groups" type="button" role="tab">
                            {{ 'All Option Groups' }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-sort" role="tab" id="sortTab">
                            <i class="ri-sort-asc align-middle me-1"></i> Sort Groups
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body tab-content">
                <div class="tab-pane fade show active" id="all-groups" role="tabpanel">
                    <div class="table-responsive">
                        <table id="optionGroupTable" class="table table-bordered table-striped w-100">
                            <thead>
                                <tr>
                                    <th>{{ 'Serial' }}</th>
                                    <th>{{ 'Group Name' }}</th>
                                    <th>{{ 'Type' }}</th>
                                    <th>{{ 'Values' }}</th>
                                    <th>{{ 'Status' }}</th>
                                    <th>{{ 'Action' }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-sort" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <p class="text-muted mb-0"><i class="ri-drag-move-2-line align-middle me-1"></i> Drag and drop groups to reorder them. This order controls the Excel columns and variant display. Changes are saved automatically.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="refreshSortList">
                            <i class="ri-refresh-line align-middle me-1"></i> Refresh
                        </button>
                    </div>
                    <div id="sortableGroups" class="sortable-list" style="min-height:200px;">
                        <div class="text-center py-5 text-muted" id="sortLoading">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2">Click "Sort Groups" tab to load...</p>
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
            $('#optionGroupTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('option-groups.index') }}",
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
                        data: 'type',
                        name: 'type',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'values_count',
                        name: 'values_count',
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
                var group_id = $(this).data('id');
                var status = $(this).prop('checked') ? 1 : 0;

                $.ajax({
                    url: '/admin/option-groups/toggle-status',
                    method: "POST",
                    data: {
                        group_id: group_id,
                        status: status,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(d) {
                        reloadTable('#optionGroupTable');
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

            var url = "{{ URL::to('/admin/option-groups') }}";
            var upurl = "{{ URL::to('/admin/option-groups/update') }}";

            $("#addBtn").click(function() {
                if ($(this).val() == 'Create') {
                    var form_data = new FormData();
                    form_data.append("name", $("#name").val());
                    form_data.append("type", $("#type").val());

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
                            reloadTable('#optionGroupTable');
                            var table = $('#optionGroupTable').DataTable();
                            table.page('last').draw(false);
                            clearform();
                        },
                        error: function(xhr, status, error) {
                            hideLoader();
                            if (xhr.status === 422) {
                                let firstError = Object.values(xhr.responseJSON.errors)[0][0];
                                showError(firstError);
                            } else {
                                showError(xhr.responseJSON?.message ?? "{{ 'Error creating option group' }}");
                            }
                            console.error(xhr.responseText);
                        }
                    });
                }

                if ($(this).val() == 'Update') {
                    var form_data = new FormData();
                    form_data.append("name", $("#name").val());
                    form_data.append("type", $("#type").val());
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
                            hideLoader();
                            $("#addThisFormContainer").slideUp(300);
                            setTimeout(() => {
                                $("#newBtn").show(200);
                            }, 300);
                            reloadTable('#optionGroupTable');
                            clearform();
                        },
                        error: function(xhr, status, error) {
                            hideLoader();
                            if (xhr.status === 422) {
                                let firstError = Object.values(xhr.responseJSON.errors)[0][0];
                                showError(firstError);
                            } else {
                                showError(xhr.responseJSON?.message ?? "{{ 'Error updating option group' }}");
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
                $("#type").val(data.type);
                $("#codeid").val(data.id);
                $("#addBtn").val('Update');
                $("#addBtn").html("{{ 'Update' }}");
                $("#addThisFormContainer").show(300);
                $("#newBtn").hide(100);
            }

            function clearform() {
                $('#createThisForm')[0].reset();
                $("#addBtn").val('Create');
                $("#addBtn").html("{{ 'Create' }}");
                $("#cardTitle").text("{{ 'Add New Option Group' }}");
            }
        });
    </script>

    <script>
        // ===== SORTABLE GROUPS =====
        var sortableGroupsLoaded = false;

        function loadGroupSortList() {
            if (sortableGroupsLoaded) return;
            $.get("{{ route('option-groups.sortList') }}", function(groups) {
                var html = '';
                if (groups.length === 0) {
                    html = '<div class="text-center py-5 text-muted"><i class="ri-inbox-line fs-1"></i><p class="mt-2">No option groups found</p></div>';
                } else {
                    groups.forEach(function(g, i) {
                        html += '<div class="sort-item" data-id="' + g.id + '">';
                        html += '  <div class="d-flex align-items-center gap-3">';
                        html += '    <span class="sort-handle text-muted"><i class="ri-drag-move-line fs-5"></i></span>';
                        html += '    <div class="flex-grow-1">';
                        html += '      <div class="fw-semibold">' + (g.name || '') + '</div>';
                        html += '      <small class="text-muted">' + (g.values_count || 0) + ' values</small>';
                        html += '    </div>';
                        html += '    <span class="badge bg-light text-dark sort-position">#' + (i + 1) + '</span>';
                        html += '  </div>';
                        html += '</div>';
                    });
                }
                $('#sortableGroups').html(html);
                sortableGroupsLoaded = true;
                initGroupSortable();
            });
        }

        function initGroupSortable() {
            $('#sortableGroups').sortable({
                handle: '.sort-handle',
                placeholder: 'sort-placeholder',
                tolerance: 'pointer',
                opacity: 0.8,
                cursor: 'grabbing',
                update: function() {
                    var ids = $('#sortableGroups').sortable('toArray', { attribute: 'data-id' });
                    $('#sortableGroups .sort-item').each(function(i) {
                        $(this).find('.sort-position').text('#' + (i + 1));
                    });
                    $.ajax({
                        url: "{{ route('option-groups.sortUpdate') }}",
                        method: "POST",
                        data: {
                            ids: ids,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(d) {
                            showSuccess(d.message);
                            reloadTable('#optionGroupTable');
                        },
                        error: function() {
                            showError('Failed to update sort order');
                        }
                    });
                }
            }).disableSelection();
        }

        $('#sortTab').on('shown.bs.tab', function() {
            loadGroupSortList();
        });

        $('#refreshSortList').on('click', function() {
            sortableGroupsLoaded = false;
            $('#sortableGroups').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading groups...</p></div>');
            loadGroupSortList();
        });

        $('#optionGroupTable').on('draw.dt', function() {
            sortableGroupsLoaded = false;
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
