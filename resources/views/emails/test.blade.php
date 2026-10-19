@include('emails.partials.header')
<h1 style="font-size:22px;margin:0 0 12px;">Your mail settings work</h1>
<p style="font-size:15px;margin:0 0 12px;">This test email was sent from Shop Settings. If you are reading this, shoppers will receive order, reset-password and stock-alert emails too.</p>
<p style="font-size:13px;color:#6b7280;margin:0;">Sent {{ now()->format('d M Y, H:i') }}.</p>
@include('emails.partials.footer')
