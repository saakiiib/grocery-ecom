@extends('admin.pages.master')
@section('title', 'Terms of Supply')

@section('content')
    <div class="container-fluid">
        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">Terms of Supply <small class="text-muted">— shown on the storefront Terms page</small></h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.content.terms.update') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Page body</label>
                                <textarea class="form-control summernote" name="terms_and_conditions" rows="8">{{ old('terms_and_conditions', $companyDetails?->terms_and_conditions ?? '') }}</textarea>
                                @error('terms_and_conditions')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Save Terms</button>
                            <a href="{{ route('terms') }}" target="_blank" class="btn btn-soft-secondary ms-2">View page</a>
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
