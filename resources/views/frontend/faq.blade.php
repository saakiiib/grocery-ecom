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
    <div class="container" style="max-width:640px;padding-bottom:4rem;">
        @forelse ($faqsJson as $f)
            <div style="margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid var(--border);">
                <h3 style="font-size:1.1rem;margin-bottom:0.5rem;">{{ $f['q'] }}</h3>
                <p class="text-muted">{{ $f['a'] }}</p>
            </div>
        @empty
            <div class="empty-state">
                <h2>No questions yet</h2>
                <p>Answers will appear here soon.</p>
            </div>
        @endforelse
    </div>
</main>
@endsection
