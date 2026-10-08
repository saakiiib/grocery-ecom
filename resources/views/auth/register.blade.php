@extends('frontend.layout')
@section('title', 'Create account')
@section('content')

<main>
    <div class="container" style="padding:2.5rem 1.25rem 4rem;">
        <div class="auth-split">
            @include('auth.side')
            <div class="auth-form">
                <h1>Join Alam Mini Market</h1>
                <p class="text-muted" style="margin-bottom:1.5rem;">Track orders, reorder favourites, check out faster.</p>
                <form method="POST" action="{{ route('register.store') }}">
                    @csrf
                    @if (request()->has('redirect'))
                        <input type="hidden" name="redirect" value="{{ request('redirect') }}">
                    @endif
                    <div class="form-group">
                        <label for="name">Full name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name">
                        @error('name')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="150" autocomplete="email">
                            @error('email')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone <span class="text-muted">(optional)</span></label>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="30" autocomplete="tel">
                            @error('phone')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Password <span class="text-muted">(6 digits)</span></label>
                        <div class="pw-wrap">
                            <input id="password" type="password" name="password" required inputmode="numeric" maxlength="6" autocomplete="new-password">
                            <button type="button" class="pw-eye" data-eye="password" aria-label="Show password"><x-icon name="eye" /><x-icon name="eye-off" class="pw-eye-off" /></button>
                        </div>
                        @error('password')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Confirm password</label>
                        <div class="pw-wrap">
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                            <button type="button" class="pw-eye" data-eye="password_confirmation" aria-label="Show password"><x-icon name="eye" /><x-icon name="eye-off" class="pw-eye-off" /></button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark btn-block">Create account</button>
                    <p class="text-muted" style="font-size:14px;margin-top:1rem;text-align:center;">
                        Already have one? <a href="{{ route('login', request()->has('redirect') ? ['redirect' => request('redirect')] : []) }}">Sign in</a>
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
