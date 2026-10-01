@extends('frontend.layout')
@section('title', 'Frequently asked questions')

@php
    $grouped = $faqsJson->groupBy(fn ($f) => $f['badge'] ?? 'General');
@endphp

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">Help</p>
            <h1>Frequently asked questions</h1>
        </div>
    </div>
    <div class="container" style="max-width:680px;padding-bottom:4rem;">
        @forelse ($grouped as $label => $items)
            @if ($grouped->count() > 1)
                <h2 class="faq-group">{{ $label }}</h2>
            @endif
            @foreach ($items as $f)
                <details class="faq-item" @if ($loop->parent->first && $loop->first) open @endif>
                    <summary class="faq-q"><span>{{ $f['q'] }}</span><x-icon name="chevron-down" /></summary>
                    <div class="faq-a"><p>{{ $f['a'] }}</p></div>
                </details>
            @endforeach
        @empty
            <div class="empty-state">
                <h2>No questions yet</h2>
                <p>Answers will appear here soon.</p>
            </div>
        @endforelse
    </div>
</main>
@endsection
