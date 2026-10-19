@include('emails.partials.header')
<h1 style="font-size:22px;margin:0 0 12px;">Your receipt</h1>
<p style="font-size:15px;margin:0 0 12px;">Hello {{ $order->name }}.</p>
<p style="font-size:15px;margin:0 0 20px;">Thank you for shopping at <strong>{{ \App\Models\CompanyDetails::cached()->company_name ?: 'Alam Mini Market' }}</strong>.</p>
<h2 style="font-size:16px;margin:0 0 12px;">What happens now?</h2>
@include('emails.orders.partials._steps', ['current' => $order->status_slug])
@include('emails.orders.partials._receipt')
@include('emails.partials.footer')
