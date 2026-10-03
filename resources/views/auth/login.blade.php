@extends('frontend.layout')
@section('title', 'Sign in')
@section('content')

<main>
    <div class="container" style="padding:2.5rem 1.25rem 4rem;">
        <div class="auth-split">
            @include('auth.side')
            <div class="auth-form">
                <h1>Welcome back</h1>
                <p class="text-muted" style="margin-bottom:1.5rem;">Sign in to track orders and check out faster.</p>
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    @if (request()->has('redirect'))
                        <input type="hidden" name="redirect" value="{{ request('redirect') }}">
                    @endif
                    <div class="form-group">
                        <label for="login">Email or phone</label>
                        <input id="login" type="text" name="login" value="{{ old('login') }}" placeholder="you@example.com or 07…" required autofocus autocomplete="username">
                        @error('login')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="pw-wrap">
                            <input id="password" type="password" name="password" required autocomplete="current-password">
                            <button type="button" class="pw-eye" data-eye="password" aria-label="Show password"><x-icon name="eye" /><x-icon name="eye-off" class="pw-eye-off" /></button>
                        </div>
                        @error('password')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                    </div>
                    <label style="display:flex;align-items:center;gap:0.5rem;font-size:14px;margin-bottom:1.25rem;cursor:pointer;min-height:44px;">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} style="width:18px;height:18px;"> Remember me
                    </label>
                    <button type="submit" class="btn btn-dark btn-block">Sign in</button>
                    <p class="text-muted" style="font-size:14px;margin-top:1rem;text-align:center;">
                        New here? <a href="{{ route('register', request()->has('redirect') ? ['redirect' => request('redirect')] : []) }}">Create an account</a>
                    </p>
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
