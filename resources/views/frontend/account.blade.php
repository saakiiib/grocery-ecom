@extends('frontend.layout')
@section('title', 'My account')

@php
    $pwErrors = $errors->has('current_password') || $errors->has('password');
    $profileErrors = $errors->has('name') || $errors->has('phone');
    $defaultTab = $pwErrors ? 'password' : ($profileErrors ? 'details' : 'orders');
@endphp

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <h1>Hello, {{ $user->name }}</h1>
            <p>Track orders, buy favourites again, keep your details fresh.</p>
        </div>
    </div>
    <div class="container" style="padding-bottom:4rem;">
        @if (session('status'))
            <div class="auth-card" style="border-color:#1A2E22;margin-bottom:1.5rem;">{{ session('status') }}</div>
        @endif

        <div class="portal">
            <aside class="portal-side">
                <div class="portal-user">
                    <span class="avatar-initial avatar-lg">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <div>
                        <strong>{{ $user->name }}</strong>
                        <span class="text-muted" style="display:block;font-size:13px;">{{ $user->email }}</span>
                    </div>
                </div>
                <div class="portal-points">
                    <x-icon name="zap" />
                    <span><strong>{{ $pointsBalance }}</strong> points</span>
                </div>
                <nav class="portal-nav" aria-label="Account">
                    <button type="button" class="portal-link" data-portal-tab="orders"><x-icon name="package" />Orders</button>
                    <button type="button" class="portal-link" data-portal-tab="loyalty"><x-icon name="zap" />Loyalty</button>
                    <button type="button" class="portal-link" data-portal-tab="details"><x-icon name="user" />Your details</button>
                    <button type="button" class="portal-link" data-portal-tab="password"><x-icon name="lock" />Password</button>
                    <form action="{{ route('logout') }}" method="POST" class="portal-signout">@csrf<button type="submit" class="portal-link"><x-icon name="arrow-right" />Sign out</button></form>
                </nav>
            </aside>

            <div class="portal-main" data-portal data-default="{{ $defaultTab }}">
                <section class="auth-card" data-portal-panel="orders">
                    <h2 style="font-size:1.25rem;">Your orders</h2>
                    @if ($orders->isEmpty())
                        <p class="text-muted" style="margin-top:1rem;">No orders yet — your history will live here.</p>
                        <a @spa href="{{ route('shop') }}" class="btn btn-dark" style="margin-top:1rem;">Start shopping</a>
                    @else
                        @foreach ($orders as $order)
                            @php $st = $order->status; @endphp
                            <div class="summary-row" style="align-items:center;border-bottom:1px solid var(--border);padding:0.85rem 0;">
                                <span>
                                    <strong><a @spa href="{{ route('account.order', $order->number) }}">{{ $order->number }}</a></strong>
                                    <br><span class="text-muted">{{ $order->created_at->format('j M Y') }} · {{ $order->itemCount() }} item{{ $order->itemCount() === 1 ? '' : 's' }} · £{{ number_format($order->total, 2) }} · {{ $order->paymentLabel() }}</span>
                                </span>
                                <span style="display:flex;gap:0.5rem;align-items:center;">
                                    <span class="status-pill" @if ($st) style="background:{{ $st->color }}22;color:{{ $st->color }};border:1px solid {{ $st->color }}55;" @endif>{{ $st?->name ?? ucfirst($order->status_slug) }}</span>
                                    <a @spa href="{{ route('account.order', $order->number) }}" class="btn btn-ghost btn-sm">View</a>
                                </span>
                            </div>
                        @endforeach
                        <div style="margin-top:1rem;">{{ $orders->links() }}</div>
                    @endif
                </section>

                <section class="auth-card" data-portal-panel="loyalty">
                    <h2 style="font-size:1.25rem;">Loyalty points</h2>
                    <p style="font-size:2rem;font-weight:700;margin:0.5rem 0;">{{ $pointsBalance }} <span class="text-muted" style="font-size:0.9rem;font-weight:400;">points · worth £{{ number_format($pointsBalance * \App\Models\UserPoint::value(), 2) }}</span></p>
                    <p class="text-muted" style="font-size:14px;">Earn {{ \App\Models\UserPoint::perPound() }} point per £1 when an order is delivered. Spend {{ \App\Models\UserPoint::minRedeem() }}+ points at checkout.</p>
                    @if ($pointsHistory->isNotEmpty())
                        @foreach ($pointsHistory as $entry)
                            <div class="summary-row" style="align-items:center;">
                                <span>{{ $entry->description }}<br><span class="text-muted">{{ $entry->created_at->format('j M Y, H:i') }} · {{ ucfirst($entry->type) }}</span></span>
                                <span style="font-weight:700;color:{{ $entry->points >= 0 ? '#1A2E22' : '#B91C1C' }};">{{ $entry->points >= 0 ? '+' : '' }}{{ $entry->points }}</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted" style="font-size:14px;">No points yet — they land here after your first delivered order.</p>
                    @endif
                </section>

                <section class="auth-card" data-portal-panel="details">
                    <h2 style="font-size:1.25rem;margin-bottom:1.25rem;">Your details</h2>
                    <form method="POST" action="{{ route('account.profile') }}">
                        @csrf
                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="ac-name">Full name</label>
                                <input id="ac-name" type="text" name="name" required maxlength="100" value="{{ old('name', $user->name) }}">
                                @error('name')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-group">
                                <label for="ac-phone">Phone</label>
                                <input id="ac-phone" type="tel" name="phone" maxlength="30" value="{{ old('phone', $user->phone) }}">
                                @error('phone')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ac-address">Street address</label>
                            <input id="ac-address" type="text" name="address" maxlength="500" value="{{ old('address', $user->address) }}">
                        </div>
                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="ac-city">Town / City</label>
                                <input id="ac-city" type="text" name="city" maxlength="100" value="{{ old('city', $user->city) }}">
                            </div>
                            <div class="form-group">
                                <label for="ac-postcode">Postcode</label>
                                <input id="ac-postcode" type="text" name="postcode" maxlength="20" value="{{ old('postcode', $user->postcode) }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="{{ $user->email }}" disabled>
                            <p class="text-muted" style="font-size:13px;margin-top:0.35rem;">Email is your login — contact us to change it.</p>
                        </div>
                        <button type="submit" class="btn btn-dark">Save details</button>
                    </form>
                </section>

                <section class="auth-card" data-portal-panel="password">
                    <h2 style="font-size:1.25rem;margin-bottom:1.25rem;">Change password</h2>
                    <form method="POST" action="{{ route('account.password') }}">
                        @csrf
                        <div class="form-group">
                            <label for="pw-current">Current password</label>
                            <input id="pw-current" type="password" name="current_password" required autocomplete="current-password">
                            @error('current_password')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="pw-new">New password <span class="text-muted">(8+ characters)</span></label>
                                <input id="pw-new" type="password" name="password" required autocomplete="new-password">
                                @error('password')<p style="color:#B91C1C;font-size:13px;margin-top:0.35rem;">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-group">
                                <label for="pw-confirm">Confirm new password</label>
                                <input id="pw-confirm" type="password" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark">Change password</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</main>
<script>
    /* Account portal tabs — var only, re-runnable under the SPA engine. */
    function egfPortalInit() {
        var root = document.querySelector('[data-portal]');
        if (!root || root._egfBound) return;
        root._egfBound = true;
        var panels = root.querySelectorAll('[data-portal-panel]');
        var links = document.querySelectorAll('[data-portal-tab]');
        var valid = ['orders', 'loyalty', 'details', 'password'];

        function show(name, push) {
            if (valid.indexOf(name) === -1) name = root.dataset.default || 'orders';
            panels.forEach(function (p) { p.hidden = p.dataset.portalPanel !== name; });
            links.forEach(function (l) {
                var on = l.dataset.portalTab === name;
                l.classList.toggle('active', on);
                if (on) l.setAttribute('aria-current', 'true');
                else l.removeAttribute('aria-current');
            });
            if (push !== false) {
                try { history.replaceState(null, '', '#' + name); } catch (e) {}
            }
        }

        links.forEach(function (l) {
            l.addEventListener('click', function () { show(l.dataset.portalTab); });
        });
        show((window.location.hash || '').replace('#', ''));
    }
    document.addEventListener('spa:loaded', egfPortalInit);
    egfPortalInit();
</script>
@endsection
