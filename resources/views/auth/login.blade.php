@extends('frontend.layout')
@section('title', 'Sign in')
@section('content')

<main>
    <div class="page-hero">
        <div class="container">
            <h1>Welcome back</h1>
            <p>Sign in to track orders and check out faster.</p>
        </div>
    </div>
    <div class="container" style="max-width:480px;padding-bottom:4rem;">
        <form method="POST" action="{{ route('login') }}" class="auth-card">
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
                <input id="password" type="password" name="password" required autocomplete="current-password">
                @error('password')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
            </div>
            <label style="display:flex;align-items:center;gap:0.5rem;font-size:14px;margin-bottom:1.25rem;cursor:pointer;">
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Remember me
            </label>
            <button type="submit" class="btn btn-dark btn-block">Sign in</button>
            <p class="text-muted" style="font-size:14px;margin-top:1rem;text-align:center;">
                New here? <a href="{{ route('register', request()->has('redirect') ? ['redirect' => request('redirect')] : []) }}">Create an account</a>
            </p>
        </form>
    </div>
</main>

@endsection
