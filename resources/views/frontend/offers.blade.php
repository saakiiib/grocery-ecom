@extends('frontend.layout')
@section('title', "Weekly offers")

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">This week only</p>
            <h1>Weekly offers</h1>
            <p>Seasonal savings on produce and pantry favourites.</p>
        </div>
    </div>
    <div class="container" style="padding-bottom:4rem;">
        @if ($offerCards->isNotEmpty())
            <div class="product-grid">
                @foreach ($offerCards as $p)
                    @include('frontend.partials.product-card', ['p' => $p])
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <h2>No offers right now</h2>
                <p>Check back soon — new savings land every week.</p>
                <a @spa href="{{ route('collections') }}" class="btn btn-dark">Shop groceries</a>
            </div>
        @endif
    </div>
</main>
@endsection
