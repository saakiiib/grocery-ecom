@php
    // $order with items loaded. Shared receipt body: slot, addresses, items, totals.
    $bill = $order->billTo();
@endphp
<p style="font-size:14px;">Order number <strong>{{ $order->number }}</strong></p>
<p style="font-size:14px;">
    Arriving <strong>{{ \Carbon\Carbon::parse($order->delivery_date)->format('l j F') }}</strong>
    ({{ $order->delivery_slot_label }})
</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:12px 0;">
    <tr>
        <td width="50%" valign="top" style="padding-right:8px;">
            <p style="font-size:12px;color:#6B7280;margin:0 0 4px;">SHIP TO</p>
            <p style="font-size:14px;margin:0;">{{ $order->name }} · {{ $order->phone }}<br>{{ $order->address }}, {{ $order->city }} {{ $order->postcode }}</p>
        </td>
        <td width="50%" valign="top" style="padding-left:8px;">
            <p style="font-size:12px;color:#6B7280;margin:0 0 4px;">BILL TO</p>
            <p style="font-size:14px;margin:0;">{{ $bill['name'] }} · {{ $bill['phone'] }}<br>{{ $bill['address'] }}, {{ $bill['city'] }} {{ $bill['postcode'] }}</p>
        </td>
    </tr>
</table>
<table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="border-top:1px solid #E5E7EB;font-size:14px;">
    @foreach ($order->items as $item)
        <tr>
            <td>{{ $item->qty }} × {{ $item->product_name }}@if ($item->status !== 'ok') (unavailable — refunded)@endif
                @if ($item->pack_label)<br><span style="color:#6B7280;font-size:12px;">{{ $item->pack_label }}</span>@endif</td>
            <td align="right">£{{ number_format($item->line_total, 2) }}</td>
        </tr>
    @endforeach
</table>
<table role="presentation" width="100%" cellpadding="4" cellspacing="0" style="border-top:1px solid #E5E7EB;font-size:14px;">
    <tr><td>Subtotal</td><td align="right">£{{ number_format($order->subtotal, 2) }}</td></tr>
    @if ($order->coupon_discount > 0)
        <tr><td>Coupon {{ $order->coupon_code }}</td><td align="right">−£{{ number_format($order->coupon_discount, 2) }}</td></tr>
    @endif
    @if ($order->points_discount > 0)
        <tr><td>Loyalty points ({{ $order->points_redeemed }})</td><td align="right">−£{{ number_format($order->points_discount, 2) }}</td></tr>
    @endif
    <tr><td>Delivery</td><td align="right">{{ $order->delivery_fee > 0 ? '£'.number_format($order->delivery_fee, 2) : 'Free' }}</td></tr>
    @if ($order->refunded_amount > 0)
        <tr><td>Refunded</td><td align="right">−£{{ number_format($order->refunded_amount, 2) }}</td></tr>
    @endif
    @if ($order->vat_amount > 0)
        <tr><td style="color:#6B7280;">Includes VAT @ {{ number_format($order->vat_percent, 2) }}%</td><td align="right" style="color:#6B7280;">£{{ number_format($order->vat_amount, 2) }}</td></tr>
    @endif
    <tr><td><strong>Total{{ $order->isPaid() ? ' paid' : '' }}</strong> ({{ $order->paymentLabel() }})</td><td align="right"><strong>£{{ number_format($order->total, 2) }}</strong></td></tr>
</table>
<p style="font-size:14px;">Track it any time with your order number and checkout phone: <a href="{{ route('track') }}">Track my order</a></p>
<p style="font-size:12px;color:#6B7280;">Evergreen Foods — fresh to your door.</p>
