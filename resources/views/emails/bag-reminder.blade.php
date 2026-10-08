@include('emails.orders.partials._header')
<div style="padding:8px 24px 24px;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <h1 style="font-size:22px;">Still thinking it over?</h1>
    <p style="font-size:15px;">Hello {{ $snapshot->user->name }} — you left <strong>{{ count($snapshot->lines ?? []) }} item{{ count($snapshot->lines ?? []) === 1 ? '' : 's' }}</strong> (£{{ number_format($snapshot->subtotal, 2) }}) in your bag:</p>
    <ul style="font-size:15px;">
        @foreach (array_slice($snapshot->lines ?? [], 0, 5) as $line)
            <li>{{ $line['qty'] }} × {{ $line['name'] }}</li>
        @endforeach
    </ul>
    <p style="font-size:15px;"><a href="{{ route('bag') }}" style="display:inline-block;background:#1A2E22;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;">Back to my bag</a></p>
    <p style="font-size:13px;color:#6b7280;">Fresh stock moves fast — checkout soon to avoid missing out.</p>
</div>
