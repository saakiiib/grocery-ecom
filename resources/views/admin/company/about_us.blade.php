@extends('admin.pages.master')
@section('title', 'About Us')

@section('content')
    <div class="container-fluid">
        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">About Us <small class="text-muted">— shown on the storefront About page</small></h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.content.about.update') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Page body</label>
                                <textarea class="form-control summernote" name="about_us" rows="8">{{ old('about_us', $companyDetails?->about_us ?? '') }}</textarea>
                                @error('about_us')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Save About Us</button>
                            <a href="{{ route('about') }}" target="_blank" class="btn btn-soft-secondary ms-2">View page</a>
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
