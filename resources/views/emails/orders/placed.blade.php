@include('emails.orders.partials._header')
<div style="padding:8px 24px 24px;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <h1 style="font-size:22px;">Your receipt</h1>
    <p style="font-size:15px;">Hello {{ $order->name }}.</p>
    <p style="font-size:15px;">Thank you for shopping at <strong>{{ \App\Models\CompanyDetails::cached()->company_name ?: 'Alam Mini Market' }}</strong>.</p>
    <h2 style="font-size:16px;margin-top:20px;">What happens now?</h2>
    @include('emails.orders.partials._steps', ['current' => $order->status_slug])
    @include('emails.orders.partials._receipt')
</div>
