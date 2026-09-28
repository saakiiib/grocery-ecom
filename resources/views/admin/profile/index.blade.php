@extends('admin.pages.master')
@section('title', 'My Profile')
@section('content')

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">{{ 'Update Profile' }}</h4>
                </div>
                <div class="card-body">
                    <form id="profileForm" enctype="multipart/form-data">
                        @csrf

                        <div class="text-center mb-4">
                            <img id="imagePreview"
                                src="{{ $admin->image ? asset($admin->image) : asset('frontend/images/default-avatar.png') }}"
                                class="rounded-circle"
                                style="width:130px; height:130px; object-fit:cover; border:3px solid #e9ecef;">
                            <div class="mt-2">
                                <label class="btn btn-outline-primary btn-sm">
                                    <i class="ri-camera-line me-1"></i> {{ 'Change Photo' }}
                                    <input type="file" name="image" id="image" class="d-none" accept=".jpg,.jpeg,.png,.gif">
                                </label>
                                <div class="text-muted small mt-1">{{ 'Max 2MB, JPG, PNG, GIF' }}</div>
                                @if ($admin->image)
                                    <div class="form-check d-inline-block mt-2" id="removeImageWrap">
                                        <input type="checkbox" class="form-check-input" id="remove_image"
                                            name="remove_image" value="1">
                                        <label class="form-check-label small" for="remove_image">{{ 'Remove current photo' }}</label>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>{{ 'Name' }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $admin->name }}" required>
                        </div>

                        <div class="mb-3">
                            <label>{{ 'Email' }}</label>
                            <input type="email" name="email" class="form-control" value="{{ $admin->email }}">
                        </div>

                        <hr>
                        <h6 class="mb-3">{{ 'Change Password' }} <span class="text-muted small">({{ 'Leave blank to keep current' }})</span></h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>{{ 'New Password' }}</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ 'Confirm Password' }}</label>
                                <input type="password" name="password_confirmation" class="form-control">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="button" id="updateBtn" class="btn btn-primary">{{ 'Update Profile' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
$(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    $('#image').change(function () {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) { $('#imagePreview').attr('src', e.target.result); };
            reader.readAsDataURL(this.files[0]);
            $('#remove_image').prop('checked', false);
            $('#removeImageWrap').hide();
        }
    });

    $('#updateBtn').click(function () {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin me-1"></i> {{ 'Updating...' }}');

        $.ajax({
            url: "{{ route('admin.profile.update') }}",
            method: 'POST',
            data: new FormData(document.getElementById('profileForm')),
            contentType: false,
            processData: false,
            success: function (res) {
                showSuccess(res.message);
                if ($('#remove_image').is(':checked')) {
                    $('#imagePreview').attr('src', "{{ asset('frontend/images/default-avatar.png') }}");
                    $('#remove_image').prop('checked', false);
                    $('#removeImageWrap').hide();
                }
                $('#image').val('');
                btn.prop('disabled', false).text('{{ 'Update Profile' }}');
            },
            error: function (xhr) {
                btn.prop('disabled', false).text('{{ 'Update Profile' }}');
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    showError(Object.values(xhr.responseJSON.errors)[0][0]);
                } else {
                    showError(xhr.responseJSON?.message ?? '{{ 'Something went wrong, please try again' }}');
                }
            }
        });
    });
});
</script>
@endsection