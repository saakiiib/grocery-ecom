@extends('frontend.layout')
@section('title', 'Gallery')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">Fresh looks</p>
            <h1>Gallery</h1>
            <p>A peek at the market, the produce, and the people behind it.</p>
        </div>
    </div>
    <div class="container" style="padding-bottom:4rem;">
        @if ($galleryJson->isNotEmpty())
            <div class="cat-grid">
                @foreach ($galleryJson as $g)
                    <div class="cat-card" data-gallery-item data-full="{{ $g['src'] }}" data-caption="{{ $g['caption'] ?? '' }}" role="button" tabindex="0" aria-label="View larger: {{ $g['caption'] ?? 'Gallery image' }}">
                        <img src="{{ $g['src'] }}" alt="{{ $g['caption'] ?? 'Gallery image' }}" loading="lazy">
                        @if ($g['caption'])
                            <div class="cat-card-overlay">
                                <span class="cat-card-title" style="font-size:1rem;">{{ $g['caption'] }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <h2>No photos yet</h2>
                <p>Fresh pictures are on their way.</p>
            </div>
        @endif
    </div>
</main>
@endsection
