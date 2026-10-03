@extends('frontend.layout')
@section('title', ($onlyOffers ? 'Offers' . ($activeCategory !== 'All' ? ' | ' . $activeCategory : '') : ($activeCategory !== 'All' ? $activeCategory . ' | ' : '')) . 'Shop all groceries')

@php
    // Pretty shop links: category and offers live in the path (/shop/lamb,
    // /shop/offers); search/sort/price/page stay in the query string.
    $shopUrl = function (array $params = []) {
        $cat = $params['category'] ?? null;
        unset($params['category']);
        $offers = $params['only_offers'] ?? null;
        unset($params['only_offers']);
        if ($cat) {
            $url = route('shop.category', ['category' => $cat]);
            if ($offers) {
                $params['only_offers'] = $offers;
            }
        } elseif ($offers) {
            $url = route('shop.offers');
        } else {
            $url = route('shop');
        }
        $qs = http_build_query(array_filter($params, fn ($v) => $v !== null && $v !== ''));
        return $qs ? $url . '?' . $qs : $url;
    };
    $keepParams = array_filter([
        'q' => $search ?: null,
        'sort' => $sort !== 'featured' ? $sort : null,
        'min_price' => $minPrice,
        'max_price' => $maxPrice,
        'only_offers' => $onlyOffers ? 1 : null,
        'diet' => $diets !== [] ? $diets : null,
        'free_from' => $freeFrom !== [] ? $freeFrom : null,
    ]);
    $clearOffersParams = array_filter([
        'category' => $activeCategorySlug,
        'q' => $search ?: null,
        'sort' => $sort !== 'featured' ? $sort : null,
        'min_price' => $minPrice,
        'max_price' => $maxPrice,
        'diet' => $diets !== [] ? $diets : null,
        'free_from' => $freeFrom !== [] ? $freeFrom : null,
    ]);
    $dietLabels = ['vegetarian' => 'Vegetarian', 'vegan' => 'Vegan', 'halal' => 'Halal', 'organic' => 'Organic', 'gluten_free' => 'Gluten-free'];
    $chipKids = $activeParent
        ? $categories->where('parent_id', $activeParent->id)->values()
        : collect();
    $moreParams = array_merge(
        $activeCategorySlug ? ['category' => $activeCategorySlug] : [],
        $keepParams,
        ['page' => $page + 1]
    );
    $formAction = $categoryPath
        ? route('shop.category', ['category' => $categoryPath])
        : ($offersPath ? route('shop.offers') : route('shop'));
@endphp

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">The Evergreen Market</p>
            <h1>{{ $onlyOffers && $activeCategory === 'All' ? 'Offers' : ($activeCategory === 'All' ? 'All groceries' : $activeCategory) }}</h1>
            <p>Good things for the everyday table.</p>
        </div>
    </div>

    <div class="container" style="padding-bottom: 4rem;">
        <div class="cat-pills" style="margin-bottom: 1.5rem;">
            <a @spa href="{{ $shopUrl($keepParams) }}"
                class="cat-pill {{ $activeCategory === 'All' && ! $onlyOffers ? 'active' : '' }}">All groceries</a>
            @foreach ($parents as $par)
                <a @spa href="{{ $shopUrl(array_merge($keepParams, ['category' => $par->slug])) }}"
                    class="cat-pill {{ ($activeParent && $activeParent->id === $par->id) ? 'active' : '' }}">{{ $par->name }}</a>
            @endforeach
        </div>

        @if ($chipKids->isNotEmpty() || $onlyOffers)
            <div class="child-chips">
                @if ($onlyOffers)
                    <a @spa href="{{ $shopUrl($clearOffersParams) }}" class="child-chip active">Offers ×</a>
                @endif
                @foreach ($chipKids as $kid)
                    <a @spa href="{{ $shopUrl(array_merge($keepParams, ['category' => $kid->slug])) }}"
                        class="child-chip {{ $activeCategorySlug === $kid->slug ? 'active' : '' }}">{{ $kid->name }}</a>
                @endforeach
            </div>
        @endif

        <div class="child-chips" style="margin-bottom:1rem;">
            @foreach ($dietLabels as $key => $label)
                @php $toggled = in_array($key, $diets) ? array_values(array_diff($diets, [$key])) : array_merge($diets, [$key]); @endphp
                <a @spa href="{{ $shopUrl(array_merge($keepParams, ['diet' => $toggled !== [] ? $toggled : null])) }}"
                    class="child-chip {{ in_array($key, $diets) ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
            @foreach ($freeFrom as $ff)
                @php $cleared = array_values(array_diff($freeFrom, [$ff])); @endphp
                <a @spa href="{{ $shopUrl(array_merge($keepParams, ['free_from' => $cleared !== [] ? $cleared : null])) }}"
                    class="child-chip active">No {{ $ff }} ×</a>
            @endforeach
        </div>

        <form method="GET" action="{{ $formAction }}" class="shop-tools">
            @if ($activeCategorySlug && ! $categoryPath)
                <input type="hidden" name="category" value="{{ $activeCategorySlug }}">
            @endif
            @if ($onlyOffers && ! $offersPath)
                <input type="hidden" name="only_offers" value="1">
            @endif
            @foreach ($diets as $d)
                <input type="hidden" name="diet[]" value="{{ $d }}">
            @endforeach
            <div class="shop-search">
                <input type="search" name="q" value="{{ $search }}" placeholder="Search the market" aria-label="Search" data-shop-search>
                <button type="submit" class="btn btn-dark btn-sm">Go</button>
            </div>
            @if ($priceCeil > $priceFloor)
                <div class="price-slider" data-price-slider data-floor="{{ $priceFloor }}" data-ceil="{{ $priceCeil }}">
                    <input type="hidden" name="min_price" value="{{ $minPrice ?? $priceFloor }}" data-price-min>
                    <input type="hidden" name="max_price" value="{{ $maxPrice ?? $priceCeil }}" data-price-max>
                    <span class="price-cap">£{{ $priceFloor }}</span>
                    <div class="price-mid">
                        <div class="price-now" data-price-range></div>
                        <div class="price-track">
                            <div class="price-rail"></div>
                            <div class="price-fill" data-price-fill></div>
                            <input type="range" data-price-lo min="{{ $priceFloor }}" max="{{ $priceCeil }}" step="1" value="{{ $minPrice ?? $priceFloor }}" aria-label="Minimum price">
                            <input type="range" data-price-hi min="{{ $priceFloor }}" max="{{ $priceCeil }}" step="1" value="{{ $maxPrice ?? $priceCeil }}" aria-label="Maximum price">
                        </div>
                    </div>
                    <span class="price-cap">£{{ $priceCeil }}</span>
                </div>
            @endif
            <div class="flex items-center gap-2">
                <span class="text-muted" style="font-size:13px;">{{ $total > $perPage ? "Showing $shown of $total" : "$total product" . ($total === 1 ? '' : 's') }}</span>
                <select name="free_from[]" multiple aria-label="Free from allergens" title="Free from allergens" style="max-width:150px;">
                    <option value="">Free from…</option>
                    @foreach ($allergens as $al)
                        <option value="{{ $al->slug }}" @selected(in_array($al->slug, $freeFrom))>No {{ $al->name }}</option>
                    @endforeach
                </select>
                <select class="sort-select" name="sort" aria-label="Sort" data-shop-sort>
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
            @if ($hasMore)
                <div class="load-more">
                    <a @spa href="{{ $shopUrl($moreParams) }}" class="btn btn-dark" data-load-more>Load more ({{ $total - $shown }} remaining)</a>
                </div>
            @endif
        @else
            <div class="empty-state">
                <h2>Nothing found</h2>
                <p>{{ $search ? 'No products matched "' . $search . '".' : 'No products match these filters yet.' }}</p>
                <a @spa href="{{ route('shop') }}" class="btn btn-dark">Browse everything</a>
            </div>
        @endif
    </div>
</main>
@endsection
