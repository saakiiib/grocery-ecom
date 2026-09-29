@extends('admin.pages.master')
@section('title', 'Manage Values — ' . $group->name)
@section('content')

    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col">
                <a href="{{ route('option-groups.index') }}" class="btn btn-light">
                    <i class="ri-arrow-left-line align-middle me-1"></i> {{ 'All Option Groups' }}
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="card">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">{{ $group->name }}
                            <small class="text-muted">({{ ucfirst($group->type) }} · {{ $group->values->count() }} values)</small>
                        </h4>
                        <span class="badge {{ $group->status ? 'bg-success' : 'bg-secondary' }}">{{ $group->status ? 'Active' : 'Disabled' }}</span>
                    </div>
                    <div class="card-body">
                        <form id="createValueForm">
                            @csrf
                            <input type="hidden" id="valueCodeid" name="codeid">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label">{{ 'Value Label' }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="valueLabel" name="label" placeholder="e.g. 750g">
                                </div>
                                <div class="col-md-4 text-end">
                                    <button type="submit" id="valueAddBtn" class="btn btn-primary" value="Create">
                                        {{ 'Add Value' }}
                                    </button>
                                    <button type="button" id="valueCancelBtn" class="btn btn-light" style="display:none;">
                                        {{ 'Cancel' }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#all-values" type="button" role="tab">
                                    {{ 'Values' }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-sort-values" role="tab" id="sortValuesTab">
                                    <i class="ri-sort-asc align-middle me-1"></i> Sort Values
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body tab-content">
                        <div class="tab-pane fade show active" id="all-values" role="tabpanel">
                            <div class="table-responsive">
                                <table id="optionValueTable" class="table table-bordered table-striped w-100">
                                    <thead>
                                        <tr>
                                            <th>{{ 'Serial' }}</th>
                                            <th>{{ 'Label' }}</th>
                                            <th>{{ 'Used In' }}</th>
                                            <th>{{ 'Status' }}</th>
                                            <th>{{ 'Action' }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($group->values as $i => $value)
                                            <tr data-id="{{ $value->id }}">
                                                <td>{{ $i + 1 }}</td>
                                                <td class="fw-semibold">{{ $value->label }}</td>
                                                <td>
                                                    @php $used = $value->variants()->count(); @endphp
                                                    @if ($used)
                                                        <span class="badge bg-info">{{ $used }} variant{{ $used > 1 ? 's' : '' }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch" dir="ltr">
                                                        <input type="checkbox" class="form-check-input toggle-value-status"
                                                            data-id="{{ $value->id }}" {{ $value->status ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-soft-primary editValueBtn" data-id="{{ $value->id }}" data-label="{{ $value->label }}">
                                                        <i class="ri-pencil-fill align-middle"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-soft-danger deleteValueBtn"
                                                        data-delete-url="{{ route('option-values.delete', $value->id) }}">
                                                        <i class="ri-delete-bin-fill align-middle"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tab-sort-values" role="tabpanel">
                            <p class="text-muted"><i class="ri-drag-move-2-line align-middle me-1"></i> Drag and drop values to reorder them. Changes are saved automatically.</p>
                            <div id="sortableValues" class="sortable-list" style="min-height:150px;">
                                @foreach ($group->values as $i => $value)
                                    <div class="sort-item" data-id="{{ $value->id }}">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="sort-handle text-muted"><i class="ri-drag-move-line fs-5"></i></span>
                                            <div class="flex-grow-1 fw-semibold">{{ $value->label }}</div>
                                            <span class="badge bg-light text-dark sort-position">#{{ $i + 1 }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
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
            $('#optionValueTable').DataTable({
                paging: false,
                searching: false,
                info: false,
                ordering: false
            });

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            var storeUrl = "{{ route('option-groups.values.store', $group->id) }}";
            var updateUrl = "{{ URL::to('/admin/option-values') }}";

            $('#createValueForm').on('submit', function(e) {
                e.preventDefault();
                var mode = $('#valueAddBtn').val();
                var form_data = new FormData();
                form_data.append('label', $('#valueLabel').val());

                var ajaxUrl = mode === 'Create' ? storeUrl : updateUrl + '/' + $('#valueCodeid').val();

                showLoader();
                $.ajax({
                    url: ajaxUrl,
                    method: 'POST',
                    contentType: false,
                    processData: false,
                    data: form_data,
                    success: function(d) {
                        hideLoader();
                        showSuccess(d.message);
                        location.reload();
                    },
                    error: function(xhr) {
                        hideLoader();
                        if (xhr.status === 422) {
                            let firstError = xhr.responseJSON.errors
                                ? Object.values(xhr.responseJSON.errors)[0][0]
                                : xhr.responseJSON.message;
                            showError(firstError);
                        } else {
                            showError(xhr.responseJSON?.message ?? 'Error saving value');
                        }
                    }
                });
            });

            $('.editValueBtn').on('click', function() {
                $('#valueCodeid').val($(this).data('id'));
                $('#valueLabel').val($(this).data('label'));
                $('#valueAddBtn').val('Update');
                $('#valueAddBtn').html("{{ 'Update Value' }}");
                $('#valueCancelBtn').show();
                $('#valueLabel').focus();
            });

            $('#valueCancelBtn').on('click', function() {
                $('#valueCodeid').val('');
                $('#valueLabel').val('');
                $('#valueAddBtn').val('Create');
                $('#valueAddBtn').html("{{ 'Add Value' }}");
                $(this).hide();
            });

            $('.deleteValueBtn').on('click', function() {
                var deleteUrl = $(this).data('delete-url');
                Swal.fire({
                    title: 'Delete this value?',
                    text: 'Values used by product variants cannot be deleted.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) return;
                    showLoader();
                    $.ajax({
                        url: deleteUrl,
                        method: 'DELETE',
                        success: function(d) {
                            hideLoader();
                            showSuccess(d.message);
                            location.reload();
                        },
                        error: function(xhr) {
                            hideLoader();
                            showError(xhr.responseJSON?.message ?? 'Error deleting value');
                        }
                    });
                });
            });

            $(document).on('change', '.toggle-value-status', function() {
                var value_id = $(this).data('id');
                var status = $(this).prop('checked') ? 1 : 0;

                $.ajax({
                    url: '/admin/option-values/toggle-status',
                    method: 'POST',
                    data: {
                        value_id: value_id,
                        status: status,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(d) {
                        showSuccess(d.message);
                    },
                    error: function() {
                        showError("{{ 'Error updating status' }}");
                    }
                });
            });

            $('#sortableValues').sortable({
                handle: '.sort-handle',
                placeholder: 'sort-placeholder',
                tolerance: 'pointer',
                opacity: 0.8,
                cursor: 'grabbing',
                update: function() {
                    var ids = $('#sortableValues').sortable('toArray', { attribute: 'data-id' });
                    $('#sortableValues .sort-item').each(function(i) {
                        $(this).find('.sort-position').text('#' + (i + 1));
                    });
                    $.ajax({
                        url: "{{ route('option-groups.values.sortUpdate', $group->id) }}",
                        method: 'POST',
                        data: {
                            ids: ids,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(d) {
                            showSuccess(d.message);
                        },
                        error: function() {
                            showError('Failed to update sort order');
                        }
                    });
                }
            }).disableSelection();
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
            min-height: 60px;
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
