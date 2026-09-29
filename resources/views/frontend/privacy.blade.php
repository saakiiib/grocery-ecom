@extends('frontend.layout')
@section('title', 'Privacy policy')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">The fine print</p>
            <h1>Privacy policy</h1>
        </div>
    </div>
    <div class="container" style="max-width:720px;padding-bottom:4rem;color:var(--muted-foreground);line-height:1.7;">
        {!! $company->privacy_policy ?? '<p>Our privacy policy will appear here soon.</p>' !!}
    </div>
</main>
@endsection
