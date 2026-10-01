@extends('frontend.layout')
@section('title', $product->name)

@php
    $groups = $productJson['optionGroups'];
    $pickerVariants = collect($productJson['variants'])->map(function ($v) {
        $pairs = collect($v['values']);
        return [
            'id' => $v['id'],
            'selling' => $v['selling'],
            'old' => ($v['offer_price'] !== null && $v['offer_price'] < $v['mrp']) ? $v['mrp'] : null,
            'save_pct' => ($v['offer_price'] !== null && $v['offer_price'] < $v['mrp'] && $v['mrp'] > 0)
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
@endphp

@section('content')
<main>
    <div class="container">
        <div class="product-detail">
            <div class="product-gallery">
                <div class="product-main-img">
                    <img src="{{ $mainImage }}" alt="{{ $product->name }}" data-main-image onerror="this.onerror=null;this.src='{{ url('placeholder.webp') }}'">
                </div>
                @if (count($gallery) > 1)
                    <div class="product-thumbs" style="display:flex;gap:.5rem;margin-top:.75rem;flex-wrap:wrap;">
                        @foreach ($gallery as $g)
                            <img src="{{ $g['src'] }}" alt="{{ $g['caption'] ?? $product->name }}" loading="lazy"
                                style="width:72px;height:72px;object-fit:cover;border-radius:10px;cursor:pointer;"
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
                </div>

                <p class="text-muted" data-stock-note style="display:none;font-size:14px;">Currently out of stock — check back soon.</p>
                <script type="application/json" id="variant-data">@json($pickerVariants)</script>
                <script type="application/json" id="variant-groups">@json(collect($groups)->map(fn ($g) => ['slug' => $g['slug']])->values())</script>

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
