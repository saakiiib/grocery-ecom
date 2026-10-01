@php
    $first = $p['cardVariants'][0] ?? null;
@endphp
<article class="product-card" data-card>
    <a @spa href="{{ $p['url'] }}" class="product-img-wrap">
        @if ($p['savePct'])
            <span class="product-badge badge-sale">Save {{ $p['savePct'] }}%</span>
        @elseif ($p['isFeatured'])
            <span class="product-badge badge-bestseller">Bestseller</span>
        @endif
        <img src="{{ $p['imgAbs'] }}" alt="{{ $p['name'] }}" loading="lazy" onerror="this.onerror=null;this.src='{{ url('placeholder.webp') }}'">
    </a>
    <div class="product-body">
        <span class="product-meta">{{ $p['category'] }}</span>
        <a @spa href="{{ $p['url'] }}" class="product-name">{{ $p['name'] }}</a>
        @if (count($p['cardVariants']) > 1)
            <div class="product-pack">
                <select aria-label="Choose pack">
                    @foreach ($p['cardVariants'] as $v)
                        <option value="{{ $v['id'] }}"
                            data-price="{{ number_format($v['selling'], 2, '.', '') }}"
                            @if ($v['mrp'] > $v['selling']) data-old="{{ number_format($v['mrp'], 2, '.', '') }}" @endif
                            data-pack="{{ $v['label'] }}"
                            @if ($v['image']) data-image="{{ $v['image'] }}" @endif
                            data-stock="{{ $v['in_stock'] ? '1' : '0' }}"
                            @selected($v['id'] === $p['variantId'])>{{ $v['label'] }} · £{{ number_format($v['selling'], 2) }}</option>
                    @endforeach
                </select>
            </div>
        @elseif ($first && $first['label'])
            <div class="product-pack"><span class="product-meta">{{ $first['label'] }}</span></div>
        @endif
        <div class="product-price-row">
            <span class="product-price" data-card-price>£{{ number_format($p['priceNum'] ?? 0, 2) }}</span>
            @if ($p['oldNum'])
                <span class="product-price-old" data-card-old>£{{ number_format($p['oldNum'], 2) }}</span>
            @else
                <span class="product-price-old" data-card-old style="display:none;"></span>
            @endif
        </div>
        <div class="product-actions">
            <button type="button" class="btn btn-dark btn-sm" data-add-to-bag
                data-variant-id="{{ $p['variantId'] }}" data-product-id="{{ $p['id'] }}"
                data-name="{{ $p['name'] }}" data-price="{{ number_format($p['priceNum'] ?? 0, 2, '.', '') }}"
                data-pack="{{ $p['packLabel'] }}" data-image="{{ $p['imgAbs'] }}"
                @disabled(!$p['inStock'])>{{ $p['inStock'] ? 'Add to bag' : 'Out of stock' }}</button>
        </div>
    </div>
</article>
