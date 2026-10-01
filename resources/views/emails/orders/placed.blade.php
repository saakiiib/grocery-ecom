<!DOCTYPE html>
<html>
<body style="margin:0;padding:0;background:#faf9f5;font-family:'Trebuchet MS',Verdana,sans-serif;color:#2c2a24;">
<div style="max-width:600px;margin:0 auto;padding:24px;">
    <h1 style="color:#2d5a3d;margin:0 0 8px;">Order {{ $order->number }} confirmed</h1>
    <p style="margin:0 0 16px;">Hi {{ $order->name }}, thank you — we have your order and will deliver it on <strong>{{ \Carbon\Carbon::parse($order->delivery_date)->format('l j F') }}</strong> ({{ $order->delivery_slot_label }}).</p>
    <table style="width:100%;border-collapse:collapse;background:#ffffff;border:1px solid #e5e1d6;border-radius:8px;">
        @foreach ($order->items as $item)
            <tr>
                <td style="padding:10px 14px;border-bottom:1px solid #f2f0e9;">{{ $item->product_name }}@if ($item->pack_label)<br><small style="color:#6b6558;">{{ $item->pack_label }}</small>@endif<br><small style="color:#6b6558;">Qty {{ $item->qty }}</small></td>
                <td style="padding:10px 14px;border-bottom:1px solid #f2f0e9;text-align:right;">£{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="padding:10px 14px;">Subtotal<br>Delivery</td>
            <td style="padding:10px 14px;text-align:right;">£{{ number_format($order->subtotal, 2) }}<br>£{{ number_format($order->delivery_fee, 2) }}</td>
        </tr>
        <tr>
            <td style="padding:10px 14px;"><strong>Total{{ $order->payment_status === 'paid' ? ' paid' : '' }}</strong></td>
            <td style="padding:10px 14px;text-align:right;"><strong>£{{ number_format($order->total, 2) }}</strong></td>
        </tr>
    </table>
    <p style="margin:16px 0 0;">Track it any time with your order number and checkout phone:<br><a href="{{ route('track') }}" style="color:#2d5a3d;">{{ route('track') }}</a></p>
    <p style="margin:16px 0 0;color:#6b6558;font-size:13px;">Evergreen Foods — fresh to your door.</p>
</div>
</body>
</html>
