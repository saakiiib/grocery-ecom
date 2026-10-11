@extends('admin.pages.master')
@section('title', 'Privacy Policy')

@section('content')
    <div class="container-fluid">

        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">Privacy Policy <small class="text-muted">— shown on the storefront Privacy page</small></h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.content.privacy.update') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Page body</label>
                                <textarea class="form-control summernote" name="privacy_policy" rows="8">{{ old('privacy_policy', $companyDetails?->privacy_policy ?? '') }}</textarea>
                                @error('privacy_policy')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Save Privacy Policy</button>
                            <a href="{{ route('privacy') }}" target="_blank" class="btn btn-soft-secondary ms-2">View page</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $('.summernote').summernote({ height: 250 });
    </script>
@endsection
