@extends('frontend.layout')
@section('title', ($activeCategory !== 'All' ? $activeCategory . ' | ' : '') . 'Shop all groceries')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">The Evergreen Market</p>
            <h1>{{ $activeCategory === 'All' ? 'All groceries' : $activeCategory }}</h1>
            <p>Good things for the everyday table.</p>
        </div>
    </div>

    <div class="container" style="padding-bottom: 4rem;">
        <div class="cat-pills" style="margin-bottom: 1.5rem;">
            <a @spa href="{{ route('collections', array_filter(['q' => $search ?: null, 'sort' => $sort !== 'featured' ? $sort : null])) }}"
                class="cat-pill {{ $activeCategory === 'All' ? 'active' : '' }}">All groceries</a>
            @foreach ($categories as $c)
                <a @spa href="{{ route('collections', array_filter(['category' => $c->slug, 'q' => $search ?: null, 'sort' => $sort !== 'featured' ? $sort : null])) }}"
                    class="cat-pill {{ $activeCategory === $c->name ? 'active' : '' }}">{{ $c->name }}</a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('collections') }}" class="shop-tools">
            @if ($activeCategorySlug)
                <input type="hidden" name="category" value="{{ $activeCategorySlug }}">
            @endif
            <div class="shop-search">
                <input type="search" name="q" value="{{ $search }}" placeholder="Search the market" aria-label="Search">
                <button type="submit" class="btn btn-dark btn-sm">Go</button>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-muted" style="font-size:13px;">{{ $productsJson->count() }} product{{ $productsJson->count() === 1 ? '' : 's' }}</span>
                <select class="sort-select" name="sort" aria-label="Sort" onchange="this.form.submit()">
                    <option value="featured" @selected($sort === 'featured')>Featured</option>
                    <option value="price_asc" @selected($sort === 'price_asc')>Price: low to high</option>
                    <option value="price_desc" @selected($sort === 'price_desc')>Price: high to low</option>
                    <option value="offers" @selected($sort === 'offers')>Offers first</option>
                    <option value="name" @selected($sort === 'name')>Name A–Z</option>
                </select>
            </div>
        </form>

        @if ($productsJson->isNotEmpty())
            <div class="product-grid">
                @foreach ($productsJson as $p)
                    @include('frontend.partials.product-card', ['p' => $p])
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <h2>Nothing found</h2>
                <p>{{ $search ? 'No products matched "' . $search . '".' : 'No products in this category yet.' }}</p>
                <a @spa href="{{ route('collections') }}" class="btn btn-dark">Browse everything</a>
            </div>
        @endif
    </div>
</main>
@endsection
