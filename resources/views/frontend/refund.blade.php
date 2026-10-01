@extends('frontend.layout')
@section('title', 'Refund policy')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">The fine print</p>
            <h1>Refund policy</h1>
        </div>
    </div>
    <div class="container" style="max-width:720px;padding-bottom:4rem;color:var(--muted-foreground);line-height:1.7;">
        <p>Something wrong with your order? Tell us within 48 hours of delivery and we will put it right — a replacement, a refund, or loyalty points, your choice.</p>
        <p>Perishable items (meat, dairy, bakery) cannot be returned once accepted, but if they arrive below standard we refund or replace them with a photo as proof. Non-perishables in sealed condition can be returned within 14 days.</p>
        <p>Refunds go back to the original payment method within 5 working days. Cash-on-delivery refunds are issued as loyalty points or bank transfer.</p>
        <p>To start a refund, <a @spa href="{{ route('contact') }}">contact us</a> with your order number.</p>
    </div>
</main>
@endsection
