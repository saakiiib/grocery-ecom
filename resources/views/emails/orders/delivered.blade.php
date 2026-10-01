<!DOCTYPE html>
<html>
<body style="margin:0;padding:0;background:#faf9f5;font-family:'Trebuchet MS',Verdana,sans-serif;color:#2c2a24;">
<div style="max-width:600px;margin:0 auto;padding:24px;">
    <h1 style="color:#2d5a3d;margin:0 0 8px;">Delivered — enjoy!</h1>
    <p style="margin:0 0 16px;">Hi {{ $order->name }}, order {{ $order->number }} ({{ '£' . number_format($order->total, 2) }}) was delivered. We hope everything arrived in perfect shape.</p>
    @if ($order->points_earned > 0)
        <p style="margin:0 0 16px;">You earned <strong>{{ $order->points_earned }} loyalty points</strong> on this order — see them in <a href="{{ route('account') }}" style="color:#2d5a3d;">your account</a>.</p>
    @endif
    <p style="margin:16px 0 0;">Something not right? Reply within 48 hours and we will refund or replace it — see our <a href="{{ route('refund') }}" style="color:#2d5a3d;">refund policy</a>.</p>
    <p style="margin:16px 0 0;color:#6b6558;font-size:13px;">Evergreen Foods — fresh to your door.</p>
</div>
</body>
</html>
