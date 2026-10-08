@extends('frontend.layout')
@section('title', 'Recipes')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <h1>Recipes</h1>
            <p>Cook it tonight — add every ingredient in one tap.</p>
        </div>
    </div>
    <div class="container">
        @if ($recipes->isEmpty())
            <div class="empty-state">
                <h2>No recipes yet</h2>
                <p>Check back soon for fresh ideas.</p>
                <a @spa href="{{ route('shop') }}" class="btn btn-dark">Shop groceries</a>
            </div>
        @else
            <div class="cat-grid">
                @foreach ($recipes as $r)
                    <a @spa href="{{ route('recipes.show', $r->slug) }}" class="cat-card">
                        <img src="{{ $r->image ? url($r->image) : asset('placeholder.webp') }}" alt="{{ $r->title }}" loading="lazy">
                        <div class="cat-card-overlay">
                            @if ($r->servings)<span class="cat-card-label">{{ $r->servings }}</span>@endif
                            <span class="cat-card-title">{{ $r->title }}</span>
                            <span class="cat-card-cta">Cook it →</span>
                        </div>
                    </a>
                @endforeach
            </div>
            {{ $recipes->links() }}
        @endif
    </div>
</main>
@endsection
