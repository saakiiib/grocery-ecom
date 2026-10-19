@include('emails.partials.header')
@if ($toSlug === 'packed')
    <h1 style="font-size:22px;margin:0 0 12px;">Your order is packed</h1>
    <p style="font-size:15px;margin:0 0 16px;">Hello {{ $order->name }} — good news, order <strong>{{ $order->number }}</strong> is packed and waiting for the driver.</p>
@elseif ($toSlug === 'out_for_delivery')
    <h1 style="font-size:22px;margin:0 0 12px;">Your order is on its way</h1>
    <p style="font-size:15px;margin:0 0 16px;">Hello {{ $order->name }} — order <strong>{{ $order->number }}</strong> left the shop. Please keep your phone nearby.</p>
@else
    <h1 style="font-size:22px;margin:0 0 12px;">Your order was cancelled</h1>
    <p style="font-size:15px;margin:0 0 16px;">Hello {{ $order->name }} — order <strong>{{ $order->number }}</strong> was cancelled. Any money taken will be refunded — please contact us if it has not arrived within 5 working days.</p>
@endif
<h2 style="font-size:16px;margin:20px 0 12px;">Where things stand</h2>
@include('emails.orders.partials._steps', ['current' => $toSlug])
@include('emails.orders.partials._receipt')
@include('emails.partials.footer')
