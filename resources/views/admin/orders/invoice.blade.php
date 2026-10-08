<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->number }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #111827; margin: 0; padding: 24px; }
        table { border-collapse: collapse; width: 100%; }
        .header td { vertical-align: top; }
        .brand { font-size: 22px; font-weight: bold; color: #1A2E22; }
        .muted { color: #6B7280; }
        .title { font-size: 20px; text-align: right; }
        .amount-due { text-align: right; font-size: 13px; margin-top: 4px; }
        .addr td { vertical-align: top; width: 33%; padding: 8px 8px 8px 0; }
        .addr h4 { margin: 0 0 4px; font-size: 11px; color: #6B7280; text-transform: uppercase; }
        .addr p { margin: 0; }
        .items th { background: #F3F4F6; text-align: left; padding: 8px; font-size: 11px; }
        .items td { padding: 8px; border-top: 1px solid #E5E7EB; }
        .items .num { text-align: right; white-space: nowrap; }
        .totals td { padding: 5px 8px; }
        .totals .num { text-align: right; white-space: nowrap; }
        .grand { font-size: 14px; font-weight: bold; border-top: 2px solid #111827; }
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #E5E7EB; font-size: 11px; color: #6B7280; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:16px;">
        <button onclick="window.print()" style="padding:8px 16px;cursor:pointer;">Print</button>
        <a href="{{ route('orders.invoicePdf', $order->id) }}" style="padding:8px 16px;">Download PDF</a>
        <a href="{{ route('orders.show', $order->id) }}" style="padding:8px 16px;">Back to order</a>
    </div>
    <table class="header">
        <tr>
            <td>
                @if ($logoDataUri)<img src="{{ $logoDataUri }}" style="height:48px;" alt="">@endif
                <div class="brand">{{ $company->company_name ?: 'Alam Mini Market' }}</div>
                <div class="muted">
                    @if ($company->address1){{ $company->address1 }}@endif
                    @if ($company->address2)<br>{{ $company->address2 }}@endif
                    @if ($company->address3)<br>{{ $company->address3 }}@endif
                    @if ($company->email1)<br>{{ $company->email1 }}@endif
                    @if ($company->phone1)<br>{{ $company->phone1 }}@endif
                    @if ($company->vat_number)<br>VAT No: {{ $company->vat_number }}@endif
                    @if ($company->company_reg_number)<br>Reg No: {{ $company->company_reg_number }}@endif
                </div>
            </td>
            <td style="text-align:right;">
                <div class="title">Invoice</div>
                <div class="muted">{{ $order->created_at->format('F j, Y') }}</div>
                <div class="amount-due"><strong>AMOUNT DUE: £{{ number_format($order->isPaid() ? 0 : $order->total, 2) }}</strong><br><span class="muted">{{ $order->paymentStatusLabel() }} · {{ $order->paymentLabel() }}</span></div>
            </td>
        </tr>
    </table>
    <table class="addr" style="margin-top:16px;">
        <tr>
            <td>
                <h4>Bill to</h4>
                <p><strong>{{ $billTo['name'] }}</strong><br>{{ $billTo['address'] }}<br>{{ $billTo['city'] }} {{ $billTo['postcode'] }}<br>{{ $billTo['phone'] }}</p>
            </td>
            <td>
                <h4>Ship to</h4>
                <p><strong>{{ $order->name }}</strong><br>{{ $order->address }}<br>{{ $order->city }} {{ $order->postcode }}<br>{{ $order->phone }}</p>
            </td>
            <td>
                <h4>Invoice details</h4>
                <p>Invoice Number<br><strong>{{ $order->number }}</strong></p>
                <p style="margin-top:6px;">Order Date<br>{{ $order->created_at->format('d/m/Y') }}</p>
                <p style="margin-top:6px;">Delivery<br>{{ $order->delivery_date ? $order->delivery_date->format('D j M Y') : '—' }} · {{ $order->delivery_slot_label }}</p>
            </td>
        </tr>
    </table>
    <table class="items" style="margin-top:16px;">
        <thead>
            <tr><th>Qty</th><th>Description</th><th style="text-align:right;">Unit price</th><th style="text-align:right;">Total</th></tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->qty }}</td>
                    <td>{{ $item->product_name }}@if ($item->status !== 'ok') (unavailable — refunded)@endif
                        @if ($item->promo_label)<br><span class="muted">{{ $item->promo_label }}{{ $item->free_qty > 0 ? ' · '.$item->free_qty.' free' : '' }}</span>@endif
                        @if ($item->pack_label)<br><span class="muted">{{ $item->pack_label }}</span>@endif</td>
                    <td class="num">£{{ number_format($item->unit_price, 2) }}</td>
                    <td class="num">£{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="totals" style="margin-top:8px;">
        <tr><td>Sub Total</td><td class="num">£{{ number_format($order->subtotal, 2) }}</td></tr>
        @if ($order->coupon_discount > 0)
            <tr><td>Coupon {{ $order->coupon_code }}</td><td class="num">−£{{ number_format($order->coupon_discount, 2) }}</td></tr>
        @endif
        @if ($order->points_discount > 0)
            <tr><td>Loyalty points ({{ $order->points_redeemed }})</td><td class="num">−£{{ number_format($order->points_discount, 2) }}</td></tr>
        @endif
        @if ($order->vat_amount > 0)
            <tr><td class="muted">Includes VAT @ {{ number_format($order->vat_percent, 2) }}%</td><td class="num muted">£{{ number_format($order->vat_amount, 2) }}</td></tr>
        @endif
        @if ($order->refunded_amount > 0)
            <tr><td>Refunded</td><td class="num">−£{{ number_format($order->refunded_amount, 2) }}</td></tr>
        @endif
        <tr><td>Shipping</td><td class="num">{{ $order->delivery_fee > 0 ? '£'.number_format($order->delivery_fee, 2) : 'Free' }}</td></tr>
        <tr class="grand"><td>Order Total</td><td class="num">£{{ number_format($order->total, 2) }}</td></tr>
    </table>
    <div class="footer">
        Thank you for shopping with {{ $company->company_name ?: 'Alam Mini Market' }}.
        @if ($company->phone1) Customer service: {{ $company->phone1 }}. @endif
        Prices include VAT where applicable.
    </div>
</body>
</html>
