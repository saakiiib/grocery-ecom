@include('emails.orders.partials._header')
<div style="padding:8px 24px 24px;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <h1 style="font-size:22px;">Reset your password</h1>
    <p style="font-size:15px;">Hello {{ $user->name }} — someone (hopefully you) asked to reset the password on your account.</p>
    <p style="font-size:15px;"><a href="{{ $url }}" style="display:inline-block;background:#1A2E22;color:#fff;padding:12px 26px;border-radius:8px;text-decoration:none;">Choose a new password</a></p>
    <p style="font-size:13px;color:#6b7280;">This link expires in {{ $minutes }} minutes. If the button does not work, paste this into your browser:<br><span style="word-break:break-all;">{{ $url }}</span></p>
    <p style="font-size:13px;color:#6b7280;">Didn't ask for this? Ignore this email — your password stays exactly as it is.</p>
</div>
