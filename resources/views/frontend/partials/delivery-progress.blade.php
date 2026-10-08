@php
    $subtotal = (float) ($subtotal ?? 0);
    $minOrder = (float) ($minOrder ?? 15);
    $freeOver = (float) ($freeOver ?? 50);
    $pct = $freeOver > 0 ? min(100, max(0, $subtotal / $freeOver * 100)) : 0;
    $unlocked = $subtotal >= $freeOver && $freeOver > 0;
    $overMin = $subtotal >= $minOrder;
@endphp
<div class="delivery-progress {{ $unlocked ? 'is-free' : '' }}" data-delivery-bar data-min="{{ $minOrder }}" data-free="{{ $freeOver }}">
    <p class="delivery-msg" data-delivery-msg>
        @if ($unlocked)
            You've unlocked FREE delivery
        @elseif ($overMin)
            Add £{{ number_format($freeOver - $subtotal, 2) }} more for FREE delivery
        @else
            Minimum order £{{ number_format($minOrder, 2) }} — add £{{ number_format($minOrder - $subtotal, 2) }} more
        @endif
    </p>
    <div class="delivery-track" role="progressbar" aria-valuemin="0" aria-valuemax="{{ number_format($freeOver, 2) }}" aria-valuenow="{{ number_format(min($subtotal, $freeOver), 2) }}">
        <div class="delivery-fill" data-delivery-fill style="width: {{ number_format($pct, 1) }}%;"></div>
    </div>
    @if (! $unlocked)
        <p class="delivery-hint">Free delivery over £{{ number_format($freeOver, 2) }} · <a @spa href="{{ route('shop.offers') }}">Top up with offers</a></p>
    @endif
</div>
