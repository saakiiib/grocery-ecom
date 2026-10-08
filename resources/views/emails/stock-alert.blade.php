@include('emails.orders.partials._header')
<div style="padding:8px 24px 24px;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    @if ($alert->type === 'price_drop')
        <h1 style="font-size:22px;">Good news — the price dropped</h1>
        <p style="font-size:15px;"><strong>{{ $alert->product->name }}</strong> is now <strong>£{{ number_format($price, 2) }}</strong>.</p>
    @else
        <h1 style="font-size:22px;">Back on the shelf</h1>
        <p style="font-size:15px;"><strong>{{ $alert->product->name }}</strong> is back in stock at <strong>£{{ number_format($price, 2) }}</strong>.</p>
    @endif
    <p style="font-size:15px;"><a href="{{ route('product.show', $alert->product->slug) }}" style="display:inline-block;background:#1A2E22;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;">Shop now</a></p>
    <p style="font-size:13px;color:#6b7280;">You asked us to watch this item. It can sell out again — order soon to avoid missing out.</p>
</div>
