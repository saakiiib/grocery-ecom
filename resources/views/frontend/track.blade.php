@extends('frontend.layout')
@section('title', 'Track your order')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <h1>Track your order</h1>
            <p>Enter your order number and the phone number used at checkout — no account needed.</p>
        </div>
    </div>
    <div class="container" style="padding-bottom:4rem;max-width:640px;">
        <form method="POST" action="{{ route('track.lookup') }}" class="auth-card" style="margin-bottom:1.5rem;">
            @csrf
            <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label for="tr-number">Order number</label>
                    <input id="tr-number" type="text" name="number" required maxlength="30" value="{{ old('number', $order?->number) }}" placeholder="EGF-10001" style="text-transform:uppercase;">
                    @error('number')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label for="tr-phone">Checkout phone</label>
                    <input id="tr-phone" type="tel" name="phone" required maxlength="30" placeholder="07…">
                    @error('phone')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" class="btn btn-dark btn-block">Track order</button>
        </form>

        @if ($order)
            @php $st = $order->status; @endphp
            <div class="auth-card" style="margin-bottom:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:0.75rem;margin-bottom:1rem;">
                    <h2 style="font-size:1.25rem;">{{ $order->number }}</h2>
                    <span class="status-pill" @if ($st) style="background:{{ $st->color }}22;color:{{ $st->color }};border:1px solid {{ $st->color }}55;" @endif>{{ $st?->name ?? ucfirst($order->status_slug) }}</span>
                </div>
                <p class="text-muted">Arriving <strong>{{ $order->delivery_date->format('l j F') }}</strong> · {{ $order->delivery_slot_label }}</p>
                <p class="text-muted">{{ $order->itemCount() }} item{{ $order->itemCount() === 1 ? '' : 's' }} · £{{ number_format($order->total, 2) }} · {{ $order->paymentLabel() }}</p>
            </div>

            <div class="auth-card">
                <h2 style="font-size:1.25rem;margin-bottom:1rem;">Journey</h2>
                @foreach ($order->histories as $h)
                    @php $to = $h->toStatus(); @endphp
                    <div class="summary-row" style="align-items:start;">
                        <span>
                            <span class="status-pill" @if ($to) style="background:{{ $to->color }}22;color:{{ $to->color }};border:1px solid {{ $to->color }}55;" @endif>{{ $to?->name ?? ucfirst(str_replace('_', ' ', $h->to_slug)) }}</span>
                            @if ($h->note)<br><span class="text-muted">{{ $h->note }}</span>@endif
                        </span>
                        <span class="text-muted">{{ $h->created_at->format('j M, H:i') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</main>
@endsection
