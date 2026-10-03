@include('emails.orders.partials._header')
<div style="padding:8px 24px 24px;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <h1 style="font-size:22px;">Delivered — enjoy!</h1>
    <p style="font-size:15px;">Hi {{ $order->name }}, order <strong>{{ $order->number }}</strong> ({{ '£'.number_format($order->total, 2) }}) was delivered. We hope everything arrived in perfect shape.</p>
    @if ($order->points_earned > 0)
        <p style="font-size:15px;">You earned <strong>{{ $order->points_earned }} loyalty points</strong> on this order — see them in <a href="{{ route('account') }}">your account</a>.</p>
    @endif
    <h2 style="font-size:16px;margin-top:20px;">Where things stand</h2>
    @include('emails.orders.partials._steps', ['current' => 'delivered'])
    @include('emails.orders.partials._receipt')
    <p style="font-size:14px;">Something not right? Reply within 48 hours and we will refund or replace it — see our <a href="{{ route('refund') }}">refund policy</a>.</p>
</div>
