@extends('frontend.layout')
@section('title', 'Choose a new password')
@section('content')

<main>
    <div class="container" style="padding:2.5rem 1.25rem 4rem;">
        <div class="auth-split">
            @include('auth.side')
            <div class="auth-form">
                <h1>Choose a new password</h1>
                <p class="text-muted" style="margin-bottom:1.5rem;">At least 8 characters — make it one you haven't used elsewhere.</p>
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" placeholder="you@example.com" required autocomplete="email">
                        @error('email')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label for="password">New password</label>
                        <div class="pw-wrap">
                            <input id="password" type="password" name="password" required autocomplete="new-password">
                            <button type="button" class="pw-eye" data-eye="password" aria-label="Show password"><x-icon name="eye" /><x-icon name="eye-off" class="pw-eye-off" /></button>
                        </div>
                        @error('password')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirm new password</label>
                        <div class="pw-wrap">
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                            <button type="button" class="pw-eye" data-eye="password_confirmation" aria-label="Show password"><x-icon name="eye" /><x-icon name="eye-off" class="pw-eye-off" /></button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark btn-block">Reset password</button>
                </form>
            </div>
        </div>
    </div>
</main>
<script>
    /* Password visibility — var only, re-runnable under the SPA engine. */
    function egfAuthInit() {
        document.querySelectorAll('[data-eye]').forEach(function (btn) {
            if (btn._egfBound) return;
            btn._egfBound = true;
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.eye);
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                var icons = btn.querySelectorAll('svg');
                if (icons.length > 1) {
                    icons[0].style.display = show ? 'none' : '';
                    icons[1].style.display = show ? '' : 'none';
                }
            });
        });
        document.querySelectorAll('[data-eye]').forEach(function (btn) {
            var icons = btn.querySelectorAll('svg');
            if (icons.length > 1) icons[1].style.display = 'none';
        });
    }
    document.addEventListener('spa:loaded', egfAuthInit);
    egfAuthInit();
</script>

@endsection
