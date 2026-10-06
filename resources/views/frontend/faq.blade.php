@extends('frontend.layout')
@section('title', 'Frequently asked questions')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">Help</p>
            <h1>Frequently asked questions</h1>
        </div>
    </div>
    <div class="container" style="max-width:680px;padding-bottom:4rem;">
        @if ($faqsJson->isNotEmpty())
            @include('frontend.partials.faq-list', ['faqsJson' => $faqsJson])
        @else
            <div class="empty-state">
                <h2>No questions yet</h2>
                <p>Answers will appear here soon.</p>
            </div>
        @endif
    </div>
</main>
@endsection
