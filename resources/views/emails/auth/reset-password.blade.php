@include('emails.partials.header')
<h1 style="font-size:22px;margin:0 0 12px;">Reset your password</h1>
<p style="font-size:15px;margin:0 0 16px;">Hello {{ $user->name }} — someone (hopefully you) asked to reset the password on your account.</p>
<p style="font-size:15px;margin:0 0 4px;"><a href="{{ $url }}" style="display:inline-block;background:#1A2E22;color:#fff;padding:12px 26px;border-radius:8px;text-decoration:none;font-weight:bold;">Choose a new password</a></p>
<p style="font-size:13px;color:#6b7280;margin:16px 0 0;">This link expires in {{ $minutes }} minutes. If the button does not work, paste this into your browser:<br><span style="word-break:break-all;"><a href="{{ $url }}" style="color:#1A2E22;">{{ $url }}</a></span></p>
<p style="font-size:13px;color:#6b7280;margin:12px 0 0;">Didn't ask for this? Ignore this email — your password stays exactly as it is.</p>
@include('emails.partials.footer')
