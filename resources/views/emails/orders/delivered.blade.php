@include('emails.partials.header')
<h1 style="font-size:22px;margin:0 0 12px;">Delivered — enjoy!</h1>
<p style="font-size:15px;margin:0 0 12px;">Hi {{ $order->name }}, order <strong>{{ $order->number }}</strong> ({{ '£'.number_format($order->total, 2) }}) was delivered. We hope everything arrived in perfect shape.</p>
@if ($order->points_earned > 0)
    <p style="font-size:15px;margin:0 0 12px;">You earned <strong>{{ $order->points_earned }} loyalty points</strong> on this order — see them in <a href="{{ route('account') }}" style="color:#1A2E22;">your account</a>.</p>
@endif
<h2 style="font-size:16px;margin:0 0 12px;">Where things stand</h2>
@include('emails.orders.partials._steps', ['current' => 'delivered'])
@include('emails.orders.partials._receipt')
<p style="font-size:14px;margin:12px 0 0;">Something not right? Reply within 48 hours and we will refund or replace it — see our <a href="{{ route('refund') }}" style="color:#1A2E22;">refund policy</a>.</p>
@include('emails.partials.footer')
