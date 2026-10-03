@php
    // $current: order status slug. Email-safe stepper, no external CSS.
    $steps = ['Order placed', 'Packed', 'Out for delivery', 'Delivered'];
    $index = ['new' => 0, 'confirmed' => 0, 'packed' => 1, 'out_for_delivery' => 2, 'delivered' => 3][$current ?? 'new'] ?? 0;
@endphp
@if (($current ?? '') === 'cancelled')
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:12px 0;">
        <tr>
            <td style="background:#FEE2E2;color:#B91C1C;border:1px solid #FECACA;border-radius:999px;padding:6px 14px;font-size:13px;font-weight:bold;">Cancelled</td>
        </tr>
    </table>
@else
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:12px 0;">
        <tr>
            @foreach ($steps as $i => $label)
                @php
                    $done = $i < $index;
                    $now = $i === $index;
                    $bg = $now ? '#1A2E22' : ($done ? '#16A34A' : '#E5E7EB');
                    $fg = ($done || $now) ? '#ffffff' : '#6B7280';
                @endphp
                <td style="padding-right:6px;">
                    <span style="display:inline-block;background:{{ $bg }};color:{{ $fg }};border-radius:999px;padding:6px 12px;font-size:13px;font-weight:{{ $now ? 'bold' : 'normal' }};">{{ $done ? '✓ ' : '' }}{{ $label }}</span>
                </td>
            @endforeach
        </tr>
    </table>
@endif
