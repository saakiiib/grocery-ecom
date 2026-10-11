@php($invoiceFormat = \App\Models\Setting::get('invoice_format', 'a4'))
@php($isPickup = ($order->fulfillment ?? 'delivery') === 'pickup')
@php($shopAddress = collect([$company->address1, $company->address2, $company->address3])->filter()->join(', '))
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->number }}</title>
    @if ($invoiceFormat === 'pos')
        <style>
            body { font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000; margin: 0; padding: 0; }
            .pos { width: 72mm; margin: 0 auto; padding: 8px 4px 24px; }
            .c { text-align: center; }
            .r { text-align: right; white-space: nowrap; }
            .b { font-weight: bold; }
            .big { font-size: 17px; }
            .huge { font-size: 22px; }
            .rule { border-top: 1px dashed #000; margin: 8px 0; }
            table { border-collapse: collapse; width: 100%; }
            td { vertical-align: top; padding: 1px 0; }
            @media print {
                .pos { width: auto; padding: 0; }
                @page { margin: 0; }
            }
        </style>
    @else
        <style>
            body { font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif; font-size: 12px; color: #1f2937; margin: 0; padding: 0; }
            table { border-collapse: collapse; width: 100%; }
            .wrap { padding: 0 0 24px; }
            .accent { background: #1a2e22; height: 8px; }
            .head { padding: 22px 32px 0; }
            .brand-name { font-size: 21px; font-weight: bold; color: #1a2e22; margin: 8px 0 4px; }
            .brand-meta { font-size: 11px; color: #6b7280; line-height: 1.65; }
            .inv-title { font-size: 32px; font-weight: bold; color: #1a2e22; letter-spacing: 3px; margin: 0; }
            .inv-num { display: inline-block; background: #1a2e22; color: #ffffff; font-size: 13px; font-weight: bold; padding: 5px 14px; margin-top: 8px; }
            .inv-date { font-size: 11px; color: #6b7280; margin-top: 8px; }
            .pill { display: inline-block; font-size: 11px; font-weight: bold; padding: 4px 12px; margin-top: 8px; }
            .pill-paid { background: #dcfce7; color: #166534; }
            .pill-unpaid { background: #fef3c7; color: #92400e; }
            .body { padding: 0 32px; }
            .cards td { width: 33%; vertical-align: top; background: #f4f6f3; border: 1px solid #e2e8e0; padding: 12px 14px; }
            .cards h4 { margin: 0 0 8px; font-size: 10px; color: #5b7a63; text-transform: uppercase; letter-spacing: 1.5px; }
            .cards p { margin: 0; font-size: 12px; line-height: 1.7; }
            .cards .lbl { color: #6b7280; font-size: 11px; }
            .items th { background: #1a2e22; color: #ffffff; text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
            .items td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
            .items tr.alt td { background: #f8faf8; }
            .items .num { text-align: right; white-space: nowrap; }
            .items .qty { text-align: center; }
            .sub { font-size: 11px; color: #6b7280; }
            .payline { margin-top: 14px; font-size: 12px; background: #f4f6f3; border: 1px solid #e2e8e0; border-left: 4px solid #1a2e22; padding: 10px 14px; }
            .due { margin-top: 14px; font-size: 13px; font-weight: bold; background: #fef3c7; border: 1px solid #f59e0b; padding: 10px 14px; color: #92400e; }
            .totals td { padding: 6px 12px; }
            .totals .num { text-align: right; white-space: nowrap; }
            .grand td { font-size: 16px; font-weight: bold; color: #ffffff; background: #1a2e22; padding: 12px; }
            .grand .num { text-align: right; white-space: nowrap; }
            .footer { margin: 22px 32px 0; padding-top: 12px; border-top: 2px solid #1a2e22; font-size: 10.5px; color: #6b7280; line-height: 1.7; }
            @page { size: A4; margin: 10mm 0 12mm; }
            @media print {
                .head { padding-top: 0; }
            }
        </style>
    @endif
</head>
<body>
@if ($invoiceFormat === 'pos')
    <div class="pos">
        <div class="c">
            <div class="b big">{{ $company->company_name ?: 'Alam Mini Market' }}</div>
            @if ($shopAddress)<div>{{ $shopAddress }}</div>@endif
            @if ($company->phone1)<div>Tel: {{ $company->phone1 }}</div>@endif
            @if ($company->vat_number)<div>VAT No: {{ $company->vat_number }}</div>@endif
        </div>
        <div class="rule"></div>
        <div class="c b">*** {{ $order->isPaid() ? 'SALES RECEIPT' : 'ORDER RECEIPT' }} ***</div>
        <table style="margin-top:6px;">
            <tr><td>Order:</td><td class="r b">{{ $order->number }}</td></tr>
            <tr><td>Date:</td><td class="r">{{ $order->created_at->format('d/m/Y H:i') }}</td></tr>
            <tr><td>Type:</td><td class="r">{{ $isPickup ? 'Click+Collect' : 'Home Delivery' }}</td></tr>
            <tr><td>Slot:</td><td class="r">{{ $order->delivery_date ? $order->delivery_date->format('D j M') : '—' }} {{ $order->delivery_slot_label }}</td></tr>
            <tr><td>Name:</td><td class="r">{{ $order->name }}</td></tr>
            @if (! $isPickup)<tr><td>Addr:</td><td class="r">{{ $order->address }}, {{ $order->postcode }}</td></tr>@endif
        </table>
        <div class="rule"></div>
        <table>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->qty }} x {{ $item->product_name }}@if ($item->pack_label) ({{ $item->pack_label }})@endif @if ($item->status !== 'ok') **REFUNDED**@endif<br><span>@ £{{ number_format($item->unit_price, 2) }} each</span>@if ($item->promo_label)<br><span>{{ $item->promo_label }}{{ $item->free_qty > 0 ? ' +' . $item->free_qty . ' free' : '' }}</span>@endif</td>
                    <td class="r b">£{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </table>
        <div class="rule"></div>
        <table>
            <tr><td>Subtotal ({{ $order->items->sum('qty') }} items)</td><td class="r">£{{ number_format($order->subtotal, 2) }}</td></tr>
            @if ($order->coupon_discount > 0)<tr><td>Coupon {{ $order->coupon_code }}</td><td class="r">-£{{ number_format($order->coupon_discount, 2) }}</td></tr>@endif
            @if ($order->points_discount > 0)<tr><td>Points ({{ $order->points_redeemed }})</td><td class="r">-£{{ number_format($order->points_discount, 2) }}</td></tr>@endif
            @if ($order->refunded_amount > 0)<tr><td>Refunded</td><td class="r">-£{{ number_format($order->refunded_amount, 2) }}</td></tr>@endif
            <tr><td>{{ $isPickup ? 'Collection' : 'Delivery' }}</td><td class="r">{{ $order->delivery_fee > 0 ? '£' . number_format($order->delivery_fee, 2) : 'FREE' }}</td></tr>
            <tr><td class="b huge">TOTAL</td><td class="r b huge">£{{ number_format($order->total, 2) }}</td></tr>
            <tr><td>{{ $order->paymentLabel() }}</td><td class="r b">{{ strtoupper($order->paymentStatusLabel()) }}</td></tr>
            @if (! $order->isPaid())<tr><td class="b">DUE</td><td class="r b">£{{ number_format($order->total, 2) }}</td></tr>@endif
        </table>
        <div class="rule"></div>
        <div class="c">*** Thank you — come again! ***<br>Goods are non-returnable unless faulty.</div>
    </div>
@else
    <div class="wrap">
        <div class="accent"></div>
        <table class="head">
            <tr>
                <td style="vertical-align:top;">
                    @if ($logoDataUri)<img src="{{ $logoDataUri }}" style="height:56px;" alt="">@endif
                    <div class="brand-name">{{ $company->company_name ?: 'Alam Mini Market' }}</div>
                    <div class="brand-meta">
                        {{ $shopAddress }}
                        @if ($company->email1)<br>{{ $company->email1 }}@endif
                        @if ($company->phone1)<br>{{ $company->phone1 }}@endif
                        @if ($company->vat_number)<br>VAT No: {{ $company->vat_number }}@endif
                        @if ($company->company_reg_number)<br>Company Reg: {{ $company->company_reg_number }}@endif
                    </div>
                </td>
                <td style="text-align:right;vertical-align:top;">
                    <p class="inv-title">{{ $company->vat_number ? 'TAX INVOICE' : 'INVOICE' }}</p>
                    <span class="inv-num">{{ $order->number }}</span>
                    <div class="inv-date">Issued {{ $order->created_at->format('F j, Y \a\t H:i') }}</div>
                    <div><span class="pill {{ $order->isPaid() ? 'pill-paid' : 'pill-unpaid' }}">{{ strtoupper($order->paymentStatusLabel()) }}</span></div>
                </td>
            </tr>
        </table>

        <div class="body">
            <table class="cards" style="margin-top:20px;">
                <tr>
                    <td>
                        <h4>Bill to</h4>
                        <p><strong>{{ $billTo['name'] }}</strong><br>{{ $billTo['address'] }}<br>{{ $billTo['city'] }} {{ $billTo['postcode'] }}<br>{{ $billTo['phone'] }}</p>
                    </td>
                    <td>
                        <h4>Ship to</h4>
                        <p><strong>{{ $order->name }}</strong><br>@if ($isPickup) Click &amp; Collect @else {{ $order->address }} @endif<br>{{ $order->city }} {{ $order->postcode }}<br>{{ $order->phone }}</p>
                    </td>
                    <td>
                        <h4>Order details</h4>
                        <p><span class="lbl">Order date</span><br>{{ $order->created_at->format('d/m/Y') }}</p>
                        <p style="margin-top:6px;"><span class="lbl">{{ $isPickup ? 'Collection' : 'Delivery' }}</span><br>{{ $order->delivery_date ? $order->delivery_date->format('D j M Y') : '—' }} · {{ $order->delivery_slot_label }}</p>
                        <p style="margin-top:6px;"><span class="lbl">Payment</span><br>{{ $order->paymentLabel() }}</p>
                    </td>
                </tr>
            </table>

            <table class="items" style="margin-top:20px;">
                <thead>
                    <tr><th style="width:36px;text-align:center;">#</th><th style="width:44px;text-align:center;">Qty</th><th>Description</th><th style="text-align:right;">Unit price</th><th style="text-align:right;">Amount</th></tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $i => $item)
                        <tr class="{{ $i % 2 ? 'alt' : '' }}">
                            <td class="qty sub">{{ $i + 1 }}</td>
                            <td class="qty">{{ $item->qty }}</td>
                            <td><strong>{{ $item->product_name }}</strong>@if ($item->status !== 'ok') <span class="sub">(unavailable — refunded)</span>@endif
                                @if ($item->pack_label)<br><span class="sub">{{ $item->pack_label }}</span>@endif
                                @if ($item->promo_label)<br><span class="sub">{{ $item->promo_label }}{{ $item->free_qty > 0 ? ' · ' . $item->free_qty . ' free' : '' }}</span>@endif</td>
                            <td class="num">£{{ number_format($item->unit_price, 2) }}</td>
                            <td class="num">£{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table style="margin-top:12px;">
                <tr>
                    <td style="vertical-align:top;">
                        <div class="payline"><strong>Payment:</strong> {{ $order->paymentLabel() }} · {{ $order->paymentStatusLabel() }}</div>
                        @if (! $order->isPaid())<div class="due">Amount due: £{{ number_format($order->total, 2) }} — please pay {{ strtolower($order->paymentLabel()) === 'cash' ? 'on delivery' : 'to complete this order' }}.</div>@endif
                        @if ($order->notes)<div class="payline" style="border-left-color:#6b7280;"><strong>Delivery notes:</strong> {{ $order->notes }}</div>@endif
                    </td>
                    <td style="width:44%;vertical-align:top;">
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
                            <tr><td>{{ $isPickup ? 'Collection' : 'Delivery' }}</td><td class="num">{{ $order->delivery_fee > 0 ? '£' . number_format($order->delivery_fee, 2) : 'Free' }}</td></tr>
                            @if ($order->vat_amount > 0)
                                <tr><td class="sub">Includes VAT @ {{ number_format($order->vat_percent, 2) }}%</td><td class="num sub">£{{ number_format($order->vat_amount, 2) }}</td></tr>
                            @endif
                            <tr class="grand"><td>Total</td><td class="num">£{{ number_format($order->total, 2) }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            Thank you for shopping with {{ $company->company_name ?: 'Alam Mini Market' }}.
            @if ($company->phone1) Customer service: {{ $company->phone1 }}. @endif
            Prices include VAT where applicable. This invoice was generated on {{ now()->format('d/m/Y H:i') }}.
        </div>
    </div>
@endif
@if (request()->boolean('print'))
    <script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
