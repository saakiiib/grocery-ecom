@include('emails.partials.header')
@if ($alert->type === 'price_drop')
    <h1 style="font-size:22px;margin:0 0 12px;">Good news — the price dropped</h1>
    <p style="font-size:15px;margin:0 0 16px;"><strong>{{ $alert->product->name }}</strong> is now <strong>£{{ number_format($price, 2) }}</strong>.</p>
@else
    <h1 style="font-size:22px;margin:0 0 12px;">Back on the shelf</h1>
    <p style="font-size:15px;margin:0 0 16px;"><strong>{{ $alert->product->name }}</strong> is back in stock at <strong>£{{ number_format($price, 2) }}</strong>.</p>
@endif
<p style="font-size:15px;margin:0 0 16px;"><a href="{{ route('product.show', $alert->product->slug) }}" style="display:inline-block;background:#1A2E22;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-weight:bold;">Shop now</a></p>
<p style="font-size:13px;color:#6b7280;margin:0;">You asked us to watch this item. It can sell out again — order soon to avoid missing out.</p>
@include('emails.partials.footer')
