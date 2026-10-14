@include('emails.orders.partials._header')
<div style="padding:8px 24px 24px;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <h1 style="font-size:22px;">Your mail settings work</h1>
    <p style="font-size:15px;">This test email was sent from Shop Settings. If you are reading this, shoppers will receive order, reset-password and stock-alert emails too.</p>
    <p style="font-size:13px;color:#6b7280;">Sent {{ now()->format('d M Y, H:i') }}.</p>
</div>
