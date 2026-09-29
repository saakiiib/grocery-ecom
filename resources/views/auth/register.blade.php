@extends('frontend.layout')
@section('title', 'Create account')
@section('content')

<main>
    <div class="page-hero">
        <div class="container">
            <h1>Join Evergreen</h1>
            <p>Track orders, reorder favourites, check out faster.</p>
        </div>
    </div>
    <div class="container" style="max-width:480px;padding-bottom:4rem;">
        <form method="POST" action="{{ route('register.store') }}" class="auth-card">
            @csrf
            @if (request()->has('redirect'))
                <input type="hidden" name="redirect" value="{{ request('redirect') }}">
            @endif
            <div class="form-group">
                <label for="name">Full name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name">
                @error('name')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="150" autocomplete="email">
                @error('email')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="phone">Phone <span class="text-muted">(optional — handy for delivery updates)</span></label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="30" autocomplete="tel">
                @error('phone')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="password">Password <span class="text-muted">(8+ characters)</span></label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
                @error('password')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-dark btn-block">Create account</button>
            <p class="text-muted" style="font-size:14px;margin-top:1rem;text-align:center;">
                Already have one? <a href="{{ route('login', request()->has('redirect') ? ['redirect' => request('redirect')] : []) }}">Sign in</a>
            </p>
        </form>
    </div>
</main>

@endsection
