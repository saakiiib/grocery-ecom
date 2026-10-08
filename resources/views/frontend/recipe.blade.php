@extends('frontend.layout')
@section('title', $recipe->title)

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">Recipe</p>
            <h1>{{ $recipe->title }}</h1>
            @if ($recipe->servings)<p>{{ $recipe->servings }}</p>@endif
        </div>
    </div>
    <div class="container">
        @if ($recipe->image)
            <img src="{{ url($recipe->image) }}" alt="{{ $recipe->title }}" loading="lazy" style="width:100%;border-radius:1rem;object-fit:cover;max-height:380px;">
        @endif
        @if ($recipe->body)
            <div style="margin-top:1.5rem;max-width:680px;">{!! $recipe->body !!}</div>
        @endif

        <section class="section">
            <div class="section-header">
                <p class="section-label">What you need</p>
                <h2 class="section-title">Ingredients</h2>
            </div>
            @if ($recipe->ingredients->isEmpty())
                <p class="text-muted">No ingredients linked yet.</p>
            @else
                <div class="cart-layout" style="margin:0;">
                    <div>
                        @foreach ($recipe->ingredients as $ing)
                            <div class="cart-item">
                                <div>
                                    <div class="cart-item-name">{{ $ing->qty }} × {{ $ing->product?->name ?? 'Unavailable item' }}</div>
                                    @if ($ing->variant)<div class="cart-item-meta">{{ $ing->variant->combinationLabel() }}</div>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <aside class="cart-summary">
                        <h3>Cook tonight?</h3>
                        <p class="text-muted" style="font-size:14px;">Add every available ingredient to your bag in one tap.</p>
                        <form method="POST" action="{{ route('recipes.addAll', $recipe->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-dark btn-block">Add all ingredients</button>
                        </form>
                        <a @spa href="{{ route('shop') }}" class="btn btn-ghost btn-block" style="margin-top:0.5rem;">Browse the shop</a>
                    </aside>
                </div>
            @endif
        </section>
    </div>
</main>
@endsection
