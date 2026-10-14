@extends('frontend.layout')
@section('title', 'Forgot password')
@section('content')

<main>
    <div class="container" style="padding:2.5rem 1.25rem 4rem;">
        <div class="auth-split">
            @include('auth.side')
            <div class="auth-form">
                <h1>Forgot your password?</h1>
                <p class="text-muted" style="margin-bottom:1.5rem;">Enter the email you signed up with and we'll send you a reset link.</p>
                @if (session('status'))
                    <p role="status" style="border:1px solid #1A2E22;border-radius:8px;padding:0.75rem 1rem;font-size:14px;margin-bottom:1rem;">{{ session('status') }}</p>
                @endif
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus autocomplete="email">
                        @error('email')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn btn-dark btn-block">Email reset link</button>
                    <p class="text-muted" style="font-size:14px;margin-top:1rem;text-align:center;">
                        Remembered it? <a href="{{ route('login') }}">Back to sign in</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</main>

@endsection
