@extends('frontend.layout')
@section('title', $product->name)

@php
    $groups = $productJson['optionGroups'];
    $pickerVariants = collect($productJson['variants'])->map(function ($v) {
        $pairs = collect($v['values']);
        return [
            'id' => $v['id'],
            'sku' => $v['sku'] ?? null,
            'selling' => $v['selling'],
            'old' => ($v['selling'] < $v['mrp']) ? $v['mrp'] : null,
            'save_pct' => ($v['selling'] < $v['mrp'] && $v['mrp'] > 0)
                ? (int) round((($v['mrp'] - $v['selling']) / $v['mrp']) * 100) : null,
            'pack' => $pairs->pluck('label')->join(' / ') ?: ($v['sku'] ?? ''),
            'in_stock' => $v['in_stock'],
            'is_default' => $v['is_default'],
            'image' => $v['image'],
            'values' => $pairs->mapWithKeys(fn ($x) => [$x['group'] => $x['value']])->all(),
        ];
    })->values();
    $defaultVariant = $pickerVariants->firstWhere('is_default', true) ?? $pickerVariants->first();
    $defaultValues = $defaultVariant['values'] ?? [];
    $gallery = $productJson['gallery'];
    $mainImage = $defaultVariant['image'] ?? $productJson['heroImage'];
    $variantDataJson = json_encode($pickerVariants, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    $variantGroupsJson = json_encode(collect($groups)->map(fn ($g) => ['slug' => $g['slug']])->values(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

@section('content')
<main>
    <div class="container">
        <div class="product-detail">
            <div class="product-gallery">
                <div class="product-main-img">
                    <img src="{{ \App\Models\Product::thumb($mainImage, 600) }}" data-full="{{ $mainImage }}" alt="{{ $product->name }}" data-main-image onerror="this.onerror=null;this.src=this.dataset.full||'{{ url('placeholder.webp') }}'">
                </div>
                @if (count($gallery) > 1)
                    <div class="product-thumbs" style="display:flex;gap:.5rem;margin-top:.75rem;flex-wrap:wrap;">
                        @foreach ($gallery as $g)
                            <img src="{{ $g['src'] }}" data-full="{{ $g['full'] ?? $g['src'] }}" alt="{{ $g['caption'] ?? $product->name }}" loading="lazy" decoding="async"
                                style="width:72px;height:72px;object-fit:cover;border-radius:10px;cursor:pointer;"
                                onerror="this.onerror=null;this.src=this.dataset.full"
                                onclick="document.querySelector('[data-main-image]').src=this.src">
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="product-info">
                <p class="breadcrumb"><a @spa href="{{ $product->category ? route('shop.category', ['category' => $product->category->slug]) : route('shop') }}">← Back to {{ $product->category?->name ?? 'shop' }}</a></p>
                <p class="meta-label">{{ $product->category?->name ?? '' }}{{ $product->tagline ? ' · ' . $product->tagline : '' }}</p>
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;">
                    <h1 style="margin-bottom:0;">{{ $product->name }}</h1>
                    <button type="button" class="product-fav product-fav-inline {{ ($productJson['favourited'] ?? false) ? 'active' : '' }}" data-fav-toggle data-product-id="{{ $product->id }}" aria-label="Save to favourites" aria-pressed="{{ ($productJson['favourited'] ?? false) ? 'true' : 'false' }}"><x-icon name="heart" /></button>
                </div>
                @if ($product->tagline)<p class="desc">{{ $product->tagline }}</p>@endif
                @if ($productJson['diets'])
                    <p style="display:flex;gap:.4rem;flex-wrap:wrap;margin:0 0 .75rem;">
                        @foreach ($productJson['diets'] as $diet)
                            <span class="promo-tag">{{ $diet }}</span>
                        @endforeach
                    </p>
                @endif
                @if (($productJson['ratingCount'] ?? 0) > 0)
                    <p class="product-rating" style="margin:0 0 .75rem;">
                        <span class="stars">
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="{{ $i <= round($productJson['ratingAvg']) ? 'star-on' : 'star-off' }}"><x-icon name="star" /></span>
                            @endfor
                        </span>
                        {{ number_format($productJson['ratingAvg'], 1) }} ({{ $productJson['ratingCount'] }}) · <a href="#productReviews">Read reviews</a>
                    </p>
                @endif

                @php $highlights = $product->highlightList(); @endphp
                @if (! empty($highlights))
                    <ul style="margin:0 0 1rem;padding-left:1.1rem;color:var(--foreground);">
                        @foreach ($highlights as $h)
                            <li>{{ $h }}</li>
                        @endforeach
                    </ul>
                @endif

                <div data-variant-picker data-variants-id="variant-data" data-groups-id="variant-groups">
                    @foreach ($groups as $g)
                        <p style="font-weight:600;font-size:14px;margin-bottom:0.6rem;">{{ $g['name'] }}</p>
                        @if ($g['type'] === 'dropdown')
                            <div class="product-pack" data-group="{{ $g['slug'] }}" style="margin-bottom:1rem;">
                                <select data-group-select aria-label="{{ $g['name'] }}">
                                    @foreach ($g['values'] as $val)
                                        <option value="{{ $val['slug'] }}" @selected(($defaultValues[$g['slug']] ?? null) === $val['slug'])>{{ $val['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="pack-options" data-group="{{ $g['slug'] }}">
                                @foreach ($g['values'] as $val)
                                    <button type="button" class="pack-opt {{ ($defaultValues[$g['slug']] ?? null) === $val['slug'] ? 'active' : '' }}"
                                        data-value="{{ $val['slug'] }}">{{ $val['label'] }}</button>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="price-block">
                    <span class="current" data-current-price>£{{ number_format($defaultVariant['selling'] ?? 0, 2) }}</span>
                    @if ($defaultVariant['old'])
                        <span class="old" data-old-price>£{{ number_format($defaultVariant['old'], 2) }}</span>
                    @else
                        <span class="old" data-old-price style="display:none;"></span>
                    @endif
                    @if ($defaultVariant['save_pct'])
                        <span class="save" data-save-badge>Save {{ $defaultVariant['save_pct'] }}%</span>
                    @else
                        <span class="save" data-save-badge style="display:none;"></span>
                    @endif
                    <p class="text-muted" style="font-size:13px;margin-top:0.35rem;" data-pack-note>{{ $defaultVariant['pack'] }} · Price includes all taxes</p>
                    @if ($defaultVariant['sku'])<p class="text-muted" style="font-size:13px;margin-top:0.15rem;" data-sku-note>SKU: {{ $defaultVariant['sku'] }}</p>@endif
                </div>

                <p class="text-muted" data-stock-note style="display:none;font-size:14px;">Currently out of stock — check back soon.</p>
                @if ($productJson['bogoOffers'])
                    <div class="bogo-panel">
                        @foreach ($productJson['bogoOffers'] as $bo)
                            <div><strong>{{ $bo['label'] }}</strong>@if ($bo['variant']) <span class="text-muted">· {{ $bo['variant'] }}</span>@endif</div>
                        @endforeach
                        <div class="text-muted" style="font-size:13px;margin-top:.25rem;">Free units appear in your bag automatically — no code needed.</div>
                    </div>
                @endif
                @if ($productJson['flashOffers'])
                    <div class="bogo-panel" style="border-color:#fecaca;background:#fef2f2;">
                        @foreach ($productJson['flashOffers'] as $fo)
                            <div><strong style="color:#b91c1c;">Flash £{{ number_format($fo['price'], 2) }}</strong>@if ($fo['variant']) <span class="text-muted">· {{ $fo['variant'] }}</span>@endif <span class="text-muted">· ends {{ $fo['ends'] }}</span></div>
                        @endforeach
                    </div>
                @endif
                @if ($productJson['bundleOffers'])
                    <div class="bogo-panel" style="border-color:#bfdbfe;background:#eff6ff;">
                        @foreach ($productJson['bundleOffers'] as $bo)
                            <div><strong style="color:#1d4ed8;">{{ $bo['label'] }}</strong> <span class="text-muted">· {{ $bo['name'] }} — mix & match, cheapest group first</span></div>
                            @if ($bo['others'])
                                <div style="display:flex;gap:.6rem;margin-top:.6rem;flex-wrap:wrap;">
                                    @foreach ($bo['others'] as $other)
                                        <a @spa href="{{ $other['url'] }}" style="display:flex;gap:.5rem;align-items:center;border:1px solid #dbeafe;border-radius:10px;padding:.35rem .6rem .35rem .35rem;background:#fff;text-decoration:none;color:inherit;">
                                            <img src="{{ \App\Models\Product::thumb($other['image'], 150) }}" data-full="{{ $other['image'] }}" alt="{{ $other['name'] }}" loading="lazy" decoding="async" style="width:40px;height:40px;object-fit:cover;border-radius:8px;" onerror="this.onerror=null;this.src=this.dataset.full">
                                            <span style="font-size:13px;"><strong>{{ $other['name'] }}</strong><br><span class="text-muted">{{ $other['price'] }}</span></span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
                <script type="application/json" id="variant-data">{!! $variantDataJson !!}</script>
                <script type="application/json" id="variant-groups">{!! $variantGroupsJson !!}</script>

                <div class="buy-row">
                    <div class="qty-control">
                        <button type="button" data-qty-minus aria-label="Decrease">−</button>
                        <span data-qty-value>1</span>
                        <button type="button" data-qty-plus aria-label="Increase">+</button>
                    </div>
                    <button type="button" class="btn btn-dark add-to-bag" data-add-to-bag data-detail-add
                        data-variant-id="{{ $defaultVariant['id'] ?? '' }}" data-product-id="{{ $product->id }}"
                        data-name="{{ $product->name }}" data-price="{{ number_format($defaultVariant['selling'] ?? 0, 2, '.', '') }}"
                        data-pack="{{ $defaultVariant['pack'] ?? '' }}" data-image="{{ $mainImage }}"
                        @disabled(!($defaultVariant['in_stock'] ?? false))>{{ ($defaultVariant['in_stock'] ?? false) ? '+ Add to bag' : 'Out of stock' }}</button>
                </div>

                @include('frontend.partials.notify-me', ['product' => $product, 'defaultVariant' => $defaultVariant])

                @if ($product->description)
                    <div style="margin-top:1.5rem;">{!! $product->description !!}</div>
                @endif

                @if ($product->extraAttributes->isNotEmpty())
                    <div style="margin-top:1.25rem;">
                        <h3 style="font-size:1rem;margin-bottom:.5rem;">Good to know</h3>
                        <dl style="display:grid;grid-template-columns:auto 1fr;gap:.35rem 1rem;font-size:14px;">
                            @foreach ($product->extraAttributes as $a)
                                <dt style="font-weight:600;">{{ $a->label }}</dt>
                                <dd style="margin:0;">{{ $a->value }}</dd>
                            @endforeach
                        </dl>
                    </div>
                @endif

                @if ($productJson['origin'])
                    <p class="text-muted" style="font-size:14px;margin-top:1rem;">Country of origin: <strong>{{ $productJson['origin'] }}</strong></p>
                @endif

                @if ($productJson['allergens'])
                    <div style="margin-top:1rem;border:1px solid #fecaca;background:#fef2f2;border-radius:12px;padding:.8rem 1rem;font-size:14px;">
                        <strong>Allergy advice:</strong> contains {{ implode(', ', $productJson['allergens']) }}.
                    </div>
                @endif

                @if (collect($productJson['nutrition'])->filter(fn ($v) => $v !== null)->isNotEmpty())
                    <div style="margin-top:1.25rem;">
                        <h3 style="font-size:1rem;margin-bottom:.5rem;">Nutrition @if ($productJson['nutritionPer'])<span class="text-muted">({{ $productJson['nutritionPer'] }})</span>@endif</h3>
                        <dl style="display:grid;grid-template-columns:auto 1fr;gap:.35rem 1rem;font-size:14px;">
                            @foreach ($productJson['nutrition'] as $label => $value)
                                @if ($value !== null)
                                    <dt style="font-weight:600;">{{ $label }}</dt>
                                    <dd style="margin:0;">{{ $label === 'Energy (kcal)' ? number_format($value, 0).' kcal' : number_format($value, 2).' g' }}</dd>
                                @endif
                            @endforeach
                        </dl>
                    </div>
                @endif

                <div class="delivery-notes">
                    <div>
                        <x-icon name="truck" />
                        Free delivery when your order reaches £50
                    </div>
                    <div>
                        <x-icon name="leaf" />
                        Carefully selected for freshness
                    </div>
                </div>
            </div>
        </div>

        <section class="section" id="productReviews">
            <div class="section-header">
                <p class="section-label">Shopper feedback</p>
                <h2 class="section-title">Ratings & reviews</h2>
                <p class="section-desc" data-review-summary>
                    @if (($productJson['ratingCount'] ?? 0) > 0)
                        <span class="stars">
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="{{ $i <= round($productJson['ratingAvg']) ? 'star-on' : 'star-off' }}"><x-icon name="star" /></span>
                            @endfor
                        </span>
                        {{ number_format($productJson['ratingAvg'], 1) }} out of 5 · {{ $productJson['ratingCount'] }} review{{ $productJson['ratingCount'] === 1 ? '' : 's' }}
                    @else
                        No reviews yet — be the first to review this product.
                    @endif
                </p>
            </div>
            <div class="review-list" data-review-list>
                @forelse ($reviewsJson as $r)
                    <article class="review-item" data-review-id="{{ $r['id'] }}">
                        <div class="review-meta">
                            <span class="stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    <span class="{{ $i <= $r['rating'] ? 'star-on' : 'star-off' }}"><x-icon name="star" /></span>
                                @endfor
                            </span>
                            <strong>{{ $r['author'] }}</strong>
                            <span>{{ $r['date'] }}</span>
                            @if ($r['mine'])
                                <span>· Your review</span>
                                <button type="button" class="btn btn-sm btn-light" onclick="egfDeleteReview({{ $r['id'] }})">Remove</button>
                            @endif
                        </div>
                        @if ($r['title'])<p class="review-title">{{ $r['title'] }}</p>@endif
                        <p class="review-body">{{ $r['body'] }}</p>
                    </article>
                @empty
                    <p class="text-muted" data-no-reviews>Nothing here yet.</p>
                @endforelse
            </div>
            @auth
                <form class="review-form" data-review-form data-product-id="{{ $product->id }}">
                    <strong>{{ $myReview ? 'Update your review' : 'Write a review' }}</strong>
                    <div class="review-stars-input" data-stars-input>
                        @for ($i = 1; $i <= 5; $i++)
                            <label class="{{ ($myReview?->rating ?? 5) >= $i ? 'picked' : '' }}">
                                <input type="radio" name="rating" value="{{ $i }}" @checked(($myReview?->rating ?? 5) === $i)>
                                <x-icon name="star" />
                            </label>
                        @endfor
                    </div>
                    <input type="text" name="title" maxlength="120" placeholder="Review headline (optional)" value="{{ $myReview?->title }}">
                    <textarea name="body" rows="3" maxlength="2000" required placeholder="What did you think of this product?">{{ $myReview?->body }}</textarea>
                    <div>
                        <button type="submit" class="btn btn-dark btn-sm" data-review-submit>{{ $myReview ? 'Update review' : 'Submit review' }}</button>
                        <span class="text-muted review-notice" data-review-msg style="margin-left:.5rem;"></span>
                    </div>
                </form>
            @else
                <p class="review-notice"><a @spa href="{{ route('login') }}">Sign in</a> to write a review.</p>
            @endauth
        </section>

        @if (! empty($pairsJson) && $pairsJson->isNotEmpty())
            <section class="section">
                <div class="section-header">
                    <p class="section-label">Frequently bought together</p>
                    <h2 class="section-title">Pairs well with</h2>
                    <p class="section-desc">Shoppers who bought this also picked these.</p>
                </div>
                <div class="product-grid">
                    @foreach ($pairsJson as $p)
                        @include('frontend.partials.product-card', ['p' => $p])
                    @endforeach
                </div>
            </section>
        @endif

        @if (! empty($recentlyViewed) && $recentlyViewed->isNotEmpty())
            <section class="section">
                <div class="section-header">
                    <p class="section-label">Pick up where you left off</p>
                    <h2 class="section-title">Recently viewed</h2>
                </div>
                <div class="product-grid">
                    @foreach ($recentlyViewed as $p)
                        @include('frontend.partials.product-card', ['p' => $p])
                    @endforeach
                </div>
            </section>
        @endif

        @if ($relatedJson->isNotEmpty())
            <section class="section">
                <div class="section-header">
                    <p class="section-label">Keep exploring</p>
                    <h2 class="section-title">You may also like</h2>
                </div>
                <div class="product-grid">
                    @foreach ($relatedJson as $p)
                        @include('frontend.partials.product-card', ['p' => $p])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</main>
@endsection

@section('script')
<script>
    /* Product reviews — SPA re-runnable: var only, init runs directly. */
    function egfPaintStars(box, value) {
        var labels = box.querySelectorAll('label');
        labels.forEach(function (label, i) {
            label.classList.toggle('picked', i < value);
        });
    }
    function egfDeleteReview(id) {
        var meta = document.querySelector('meta[name="csrf-token"]');
        var routes = window.EGF_ROUTES || {};
        var base = routes.reviewStore || '/reviews';
        fetch(base + '/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': meta ? meta.content : '', 'Accept': 'application/json' }
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok) throw json;
                return json;
            });
        }).then(function (data) {
            var el = document.querySelector('[data-review-id="' + id + '"]');
            if (el) el.remove();
            egfRefreshSummary(data.avg, data.count);
            var msg = document.querySelector('[data-review-msg]');
            if (msg) msg.textContent = data.message || '';
        }).catch(function (err) {
            var msg = document.querySelector('[data-review-msg]');
            if (msg) msg.textContent = (err && err.message) || 'Could not remove your review.';
        });
    }
    function egfRefreshSummary(avg, count) {
        var box = document.querySelector('[data-review-summary]');
        if (!box) return;
        if (!count) {
            box.textContent = 'No reviews yet — be the first to review this product.';
            return;
        }
        var stars = '';
        var rounded = Math.round(avg);
        for (var i = 1; i <= 5; i++) {
            stars += '<span class="' + (i <= rounded ? 'star-on' : 'star-off') + '">★</span>';
        }
        box.innerHTML = '<span class="stars">' + stars + '</span> ' + Number(avg).toFixed(1) + ' out of 5 · ' + count + ' review' + (count === 1 ? '' : 's');
    }
    (function egfInitReviews() {
        var form = document.querySelector('[data-review-form]');
        if (!form || form._egfBound) return;
        form._egfBound = true;
        var starsBox = form.querySelector('[data-stars-input]');
        var msg = form.querySelector('[data-review-msg]');
        var submit = form.querySelector('[data-review-submit]');
        if (starsBox) {
            starsBox.addEventListener('change', function (e) {
                var input = e.target.closest ? e.target.closest('input[name="rating"]') : null;
                if (input) egfPaintStars(starsBox, parseInt(input.value, 10));
            });
        }
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var meta = document.querySelector('meta[name="csrf-token"]');
            var checked = form.querySelector('input[name="rating"]:checked');
            var payload = {
                product_id: parseInt(form.dataset.productId, 10),
                rating: checked ? parseInt(checked.value, 10) : 5,
                title: form.querySelector('[name="title"]').value,
                body: form.querySelector('[name="body"]').value
            };
            if (submit) submit.disabled = true;
            if (msg) msg.textContent = 'Saving…';
            var routes = window.EGF_ROUTES || {};
            fetch(routes.reviewStore || '/reviews', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': meta ? meta.content : '', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function (res) {
                if (res.status === 401) {
                    window.location.href = routes.login || '/login';
                    return null;
                }
                return res.json().then(function (json) {
                    if (!res.ok) throw json;
                    return json;
                });
            }).then(function (data) {
                if (!data) return;
                egfRefreshSummary(data.avg, data.count);
                if (msg) msg.textContent = data.message || '';
                window.location.reload();
            }).catch(function (err) {
                var first = err && err.errors ? Object.values(err.errors)[0][0] : (err && err.message);
                if (msg) msg.textContent = first || 'Could not save your review.';
                if (submit) submit.disabled = false;
            });
        });
    })();
</script>
@endsection
