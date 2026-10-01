@extends('frontend.layout')
@section('title', 'Favourites')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">Saved for later</p>
            <h1>Favourites</h1>
            <p>Your shortlist — move them to the bag whenever you're ready.</p>
        </div>
    </div>
    <div class="container" style="padding-bottom:4rem;">
        @if ($cards->isNotEmpty())
            <form method="POST" action="{{ route('favourites.move-all') }}" style="margin-bottom:1.5rem;">
                @csrf
                <button type="submit" class="btn btn-dark">Move all to bag ({{ $cards->count() }})</button>
            </form>
            <div class="product-grid">
                @foreach ($cards as $p)
                    @include('frontend.partials.product-card', ['p' => $p])
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <h2>Nothing saved yet</h2>
                <p>Tap the heart on anything you love and it will wait here.</p>
                <a @spa href="{{ route('shop') }}" class="btn btn-dark">Browse the shop</a>
            </div>
        @endif
    </div>
</main>
@endsection
