@extends('frontend.layout')
@section('title', 'Terms of supply')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">The fine print</p>
            <h1>Terms of supply</h1>
        </div>
    </div>
    <div class="container" style="max-width:720px;padding-bottom:4rem;color:var(--muted-foreground);line-height:1.7;">
        {!! $company->terms_and_conditions ?? '<p>Our terms of supply will appear here soon.</p>' !!}
    </div>
</main>
@endsection
