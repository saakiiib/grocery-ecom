@php
    // Shared order journey: visual stepper + full history. Expects $order with histories + status.
    $journeySteps = ['Placed', 'Packed', 'Out for delivery', 'Delivered'];
    $journeyIndex = ['new' => 0, 'confirmed' => 0, 'packed' => 1, 'out_for_delivery' => 2, 'delivered' => 3][$order->status_slug] ?? 0;
    $journeyCancelled = $order->status_slug === 'cancelled';
@endphp
<div class="auth-card">
    <h2 style="font-size:1.25rem;margin-bottom:1rem;">{{ $journeyTitle ?? 'Order journey' }}</h2>
    @if ($journeyCancelled)
        <p><span class="status-pill" style="background:#B91C1C22;color:#B91C1C;border:1px solid #B91C1C55;">Cancelled</span></p>
    @else
        <ol class="journey-steps">
            @foreach ($journeySteps as $i => $label)
                <li class="journey-step {{ $i < $journeyIndex ? 'done' : ($i === $journeyIndex ? 'now' : '') }}">
                    <span class="journey-dot">{{ $i < $journeyIndex ? '✓' : ($i + 1) }}</span>
                    <span class="journey-label">{{ $label }}</span>
                </li>
            @endforeach
        </ol>
    @endif
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
