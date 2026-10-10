<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->number }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 0; }
        table { border-collapse: collapse; width: 100%; }
        .wrap { padding: 28px 32px 24px; }
        .brand-name { font-size: 20px; font-weight: bold; color: #1a2e22; margin: 6px 0 4px; }
        .brand-meta { font-size: 11px; color: #6b7280; line-height: 1.6; }
        .inv-title { font-size: 30px; font-weight: bold; color: #1a2e22; letter-spacing: 2px; margin: 0; }
        .inv-num { display: inline-block; background: #1a2e22; color: #ffffff; font-size: 12px; font-weight: bold; padding: 4px 12px; margin-top: 6px; }
        .inv-date { font-size: 11px; color: #6b7280; margin-top: 6px; }
        .pill { display: inline-block; font-size: 11px; font-weight: bold; padding: 3px 12px; margin-top: 8px; }
        .pill-paid { background: #dcfce7; color: #166534; }
        .pill-unpaid { background: #fef3c7; color: #92400e; }
        .cards { border-collapse: separate; border-spacing: 8px; margin-left: -8px; margin-right: -8px; }
        .cards td { width: 33%; vertical-align: top; background: #f4f6f3; border: 1px solid #e2e8e0; padding: 10px 12px; }
        .cards h4 { margin: 0 0 6px; font-size: 10px; color: #5b7a63; text-transform: uppercase; letter-spacing: 1px; }
        .cards p { margin: 0; font-size: 12px; line-height: 1.65; }
        .cards .lbl { color: #6b7280; font-size: 11px; }
        .items th { background: #1a2e22; color: #ffffff; text-align: left; padding: 9px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .items td { padding: 9px 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .items tr.alt td { background: #f8faf8; }
        .items .num { text-align: right; white-space: nowrap; }
        .items .qty { text-align: center; }
        .sub { font-size: 11px; color: #6b7280; }
        .totals td { padding: 5px 10px; }
        .totals .num { text-align: right; white-space: nowrap; }
        .grand td { font-size: 15px; font-weight: bold; color: #1a2e22; border-top: 2px solid #1a2e22; padding: 9px 10px; }
        .grand .num { text-align: right; white-space: nowrap; }
        .payline { margin-top: 14px; font-size: 12px; background: #f4f6f3; border: 1px solid #e2e8e0; padding: 8px 12px; }
        .footer { margin-top: 22px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 10.5px; color: #6b7280; line-height: 1.7; }
        @media print {
            .wrap { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <table>
            <tr>
                <td style="vertical-align:top;">
                    @if ($logoDataUri)<img src="{{ $logoDataUri }}" style="height:52px;" alt="">@endif
                    <div class="brand-name">{{ $company->company_name ?: 'Alam Mini Market' }}</div>
                    <div class="brand-meta">
                        {{ collect([$company->address1, $company->address2, $company->address3])->filter()->join(', ') }}
                        @if ($company->email1)<br>{{ $company->email1 }}@endif
                        @if ($company->phone1)<br>{{ $company->phone1 }}@endif
                        @if ($company->vat_number)<br>VAT No: {{ $company->vat_number }}@endif
                        @if ($company->company_reg_number)<br>Company Reg: {{ $company->company_reg_number }}@endif
                    </div>
                </td>
                <td style="text-align:right;vertical-align:top;">
                    <p class="inv-title">INVOICE</p>
                    <span class="inv-num">{{ $order->number }}</span>
                    <div class="inv-date">{{ $order->created_at->format('F j, Y') }}</div>
                    <div><span class="pill {{ $order->isPaid() ? 'pill-paid' : 'pill-unpaid' }}">{{ strtoupper($order->paymentStatusLabel()) }}</span></div>
                </td>
            </tr>
        </table>

        <table class="cards" style="margin-top:18px;">
            <tr>
                <td>
                    <h4>Bill to</h4>
                    <p><strong>{{ $billTo['name'] }}</strong><br>{{ $billTo['address'] }}<br>{{ $billTo['city'] }} {{ $billTo['postcode'] }}<br>{{ $billTo['phone'] }}</p>
                </td>
                <td>
                    <h4>Ship to</h4>
                    <p><strong>{{ $order->name }}</strong><br>@if (($order->fulfillment ?? 'delivery') === 'pickup') Click &amp; Collect @else {{ $order->address }} @endif<br>{{ $order->city }} {{ $order->postcode }}<br>{{ $order->phone }}</p>
                </td>
                <td>
                    <h4>Order details</h4>
                    <p><span class="lbl">Order date</span><br>{{ $order->created_at->format('d/m/Y') }}</p>
                    <p style="margin-top:6px;"><span class="lbl">{{ ($order->fulfillment ?? 'delivery') === 'pickup' ? 'Collection' : 'Delivery' }}</span><br>{{ $order->delivery_date ? $order->delivery_date->format('D j M Y') : '—' }} · {{ $order->delivery_slot_label }}</p>
                </td>
            </tr>
        </table>

        <table class="items" style="margin-top:18px;">
            <thead>
                <tr><th style="width:44px;text-align:center;">Qty</th><th>Description</th><th style="text-align:right;">Unit price</th><th style="text-align:right;">Total</th></tr>
            </thead>
            <tbody>
                @foreach ($order->items as $i => $item)
                    <tr class="{{ $i % 2 ? 'alt' : '' }}">
                        <td class="qty">{{ $item->qty }}</td>
                        <td><strong>{{ $item->product_name }}</strong>@if ($item->status !== 'ok') <span class="sub">(unavailable — refunded)</span>@endif
                            @if ($item->pack_label)<br><span class="sub">{{ $item->pack_label }}</span>@endif
                            @if ($item->promo_label)<br><span class="sub">{{ $item->promo_label }}{{ $item->free_qty > 0 ? ' · '.$item->free_qty.' free' : '' }}</span>@endif</td>
                        <td class="num">£{{ number_format($item->unit_price, 2) }}</td>
                        <td class="num">£{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table style="margin-top:10px;">
            <tr>
                <td style="vertical-align:top;">
                    <div class="payline"><strong>Payment:</strong> {{ $order->paymentLabel() }} · {{ $order->paymentStatusLabel() }}@if (! $order->isPaid()) — <strong>due £{{ number_format($order->total, 2) }}</strong>@endif</div>
                </td>
                <td style="width:46%;vertical-align:top;">
                    <table class="totals">
                        <tr><td>Subtotal</td><td class="num">£{{ number_format($order->subtotal, 2) }}</td></tr>
                        @if ($order->coupon_discount > 0)
                            <tr><td>Coupon {{ $order->coupon_code }}</td><td class="num">−£{{ number_format($order->coupon_discount, 2) }}</td></tr>
                        @endif
                        @if ($order->points_discount > 0)
                            <tr><td>Loyalty points ({{ $order->points_redeemed }})</td><td class="num">−£{{ number_format($order->points_discount, 2) }}</td></tr>
                        @endif
                        @if ($order->refunded_amount > 0)
                            <tr><td>Refunded</td><td class="num">−£{{ number_format($order->refunded_amount, 2) }}</td></tr>
                        @endif
                        <tr><td>{{ ($order->fulfillment ?? 'delivery') === 'pickup' ? 'Collection' : 'Delivery' }}</td><td class="num">{{ $order->delivery_fee > 0 ? '£'.number_format($order->delivery_fee, 2) : 'Free' }}</td></tr>
                        @if ($order->vat_amount > 0)
                            <tr><td class="sub">Includes VAT @ {{ number_format($order->vat_percent, 2) }}%</td><td class="num sub">£{{ number_format($order->vat_amount, 2) }}</td></tr>
                        @endif
                        <tr class="grand"><td>Total</td><td class="num">£{{ number_format($order->total, 2) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="footer">
            Thank you for shopping with {{ $company->company_name ?: 'Alam Mini Market' }}.
            @if ($company->phone1) Customer service: {{ $company->phone1 }}. @endif
            Prices include VAT where applicable. This invoice was generated on {{ now()->format('d/m/Y') }}.
        </div>
    </div>
</body>
</html>
