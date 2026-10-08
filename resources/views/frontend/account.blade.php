@extends('frontend.layout')
@section('title', 'My account')

@php
    $pwErrors = $errors->has('current_password') || $errors->has('password');
    $profileErrors = $errors->has('name') || $errors->has('phone');
    $addressErrors = $errors->has('label');
    $defaultTab = $pwErrors ? 'password' : ($addressErrors ? 'addresses' : ($profileErrors ? 'details' : ($defaultTab ?? 'orders')));
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
                    <a @spa href="{{ route('account') }}" class="portal-link" data-portal-tab="orders"><x-icon name="package" />Orders</a>
                    <a @spa href="{{ route('lists.index') }}" class="portal-link" data-portal-tab="lists"><x-icon name="bookmark" />Lists</a>
                    <a @spa href="{{ route('account.loyalty') }}" class="portal-link" data-portal-tab="loyalty"><x-icon name="zap" />Loyalty</a>
                    <a @spa href="{{ route('account.addresses') }}" class="portal-link" data-portal-tab="addresses"><x-icon name="map-pin" />Addresses</a>
                    <a @spa href="{{ route('account.details') }}" class="portal-link" data-portal-tab="details"><x-icon name="user" />Your details</a>
                    <a @spa href="{{ route('account.password.form') }}" class="portal-link" data-portal-tab="password"><x-icon name="lock" />Password</a>
                    <form action="{{ route('logout') }}" method="POST" class="portal-signout">@csrf<button type="submit" class="portal-link"><x-icon name="arrow-right" />Sign out</button></form>
                </nav>
            </aside>

            <div class="portal-main" data-portal data-default="{{ $defaultTab }}">
                <section class="auth-card" data-portal-panel="orders" @if ($defaultTab !== 'orders') hidden @endif>
                    @if (! empty($buyAgain) && $buyAgain->isNotEmpty())
                        <h2 style="font-size:1.25rem;">Buy it again</h2>
                        <div class="product-grid" style="margin:1rem 0 1.75rem;">
                            @foreach ($buyAgain as $p)
                                @include('frontend.partials.product-card', ['p' => $p])
                            @endforeach
                        </div>
                    @endif
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
                                <span style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
                                    <span class="status-pill" @if ($st) style="background:{{ $st->color }}22;color:{{ $st->color }};border:1px solid {{ $st->color }}55;" @endif>{{ $st?->name ?? ucfirst($order->status_slug) }}</span>
                                    <a @spa href="{{ route('account.order', $order->number) }}" class="btn btn-ghost btn-sm">View</a>
                                    <form method="POST" action="{{ route('account.repeat', $order->number) }}" style="display:inline;">@csrf<button type="submit" class="btn btn-ghost btn-sm" title="Rebuild this order every 7 days">Repeat weekly</button></form>
                                </span>
                            </div>
                        @endforeach
                        <div style="margin-top:1rem;">{{ $orders->links() }}</div>
                    @endif
                    @if (! empty($repeats) && $repeats->isNotEmpty())
                        <h3 style="font-size:1.05rem;margin:1.75rem 0 0.5rem;">Weekly repeats</h3>
                        @foreach ($repeats as $r)
                            <div class="summary-row" style="align-items:center;border-bottom:1px solid var(--border);padding:0.7rem 0;">
                                <span>Every 7 days · next <strong>{{ $r->next_run_at->format('D j M') }}</strong><br><span class="text-muted">{{ is_array($r->items) ? count($r->items) : 0 }} lines · {{ strtoupper($r->payment_method) }} · {{ $r->is_active ? 'On' : 'Off' }}</span></span>
                                @if ($r->is_active)
                                    <form method="POST" action="{{ route('account.repeat.cancel', $r->id) }}">@csrf<button type="submit" class="btn btn-ghost btn-sm">Cancel</button></form>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </section>

                <section class="auth-card" data-portal-panel="lists" @if ($defaultTab !== 'lists') hidden @endif>
                    <h2 style="font-size:1.25rem;margin-bottom:1rem;">My lists</h2>
                    <form method="POST" action="{{ route('lists.store') }}" style="margin-bottom:1.25rem;display:flex;gap:.6rem;align-items:flex-end;flex-wrap:wrap;">
                        @csrf
                        <div class="form-group" style="flex:1;min-width:200px;margin:0;">
                            <label for="list-name">New list</label>
                            <input id="list-name" type="text" name="name" required maxlength="100" placeholder="Weekly shop">
                        </div>
                        <button type="submit" class="btn btn-dark">Create list</button>
                    </form>

                    @forelse ($lists as $list)
                        <div style="border-top:1px solid var(--border);padding:1rem 0;">
                            <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;">
                                <h3 style="font-size:1.05rem;margin:0;">{{ $list->name }} <span class="text-muted" style="font-size:.85rem;font-weight:400;">({{ $list->items->count() }} items)</span></h3>
                                <span style="display:flex;gap:.5rem;">
                                    <form method="POST" action="{{ route('lists.addAll', $list->id) }}">@csrf<button type="submit" class="btn btn-dark btn-sm">Add all to bag</button></form>
                                    <form method="POST" action="{{ route('lists.delete', $list->id) }}" onsubmit="return confirm('Delete this list?');">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-sm">Delete</button></form>
                                </span>
                            </div>
                            @if ($list->items->isNotEmpty())
                                @foreach ($list->items as $item)
                                    <div class="summary-row" style="align-items:center;border-bottom:1px solid var(--border);padding:0.7rem 0;">
                                        <span>{{ $item->qty }} × {{ $item->variant?->product?->name ?? 'Unavailable item' }}@if ($item->variant)<br><span class="text-muted">{{ $item->variant->combinationLabel() }}</span>@endif</span>
                                        <form method="POST" action="{{ route('lists.items.delete', [$list->id, $item->id]) }}">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-sm">Remove</button></form>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted" style="font-size:14px;margin:.75rem 0 0;">Empty — open any product and save its pack here. <a @spa href="{{ route('shop') }}">Browse the shop</a></p>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted" style="font-size:14px;">No lists yet — create your first list above.</p>
                    @endforelse
                </section>

                <section class="auth-card" data-portal-panel="loyalty" @if ($defaultTab !== 'loyalty') hidden @endif>
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

                <section class="auth-card" data-portal-panel="details" @if ($defaultTab !== 'details') hidden @endif>
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

                <section class="auth-card" data-portal-panel="addresses" @if ($defaultTab !== 'addresses') hidden @endif>
                    <h2 style="font-size:1.25rem;margin-bottom:0.25rem;">My addresses</h2>
                    <p class="text-muted" style="font-size:14px;">Your defaults are picked automatically at checkout.</p>
                    @foreach ($addresses as $a)
                        <div class="summary-row" style="align-items:start;border-bottom:1px solid var(--border);padding:0.85rem 0;">
                            <span>
                                <strong>{{ $a->label }}</strong>
                                @if ($a->is_default_delivery)<span class="status-pill" style="margin-left:.35rem;">Default delivery</span>@endif
                                @if ($a->is_default_billing)<span class="status-pill" style="margin-left:.35rem;">Default billing</span>@endif
                                <br><span class="text-muted">{{ $a->name }} · {{ $a->phone }}<br>{{ $a->line() }}</span>
                            </span>
                            <span style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;justify-content:flex-end;">
                                @if (!$a->is_default_delivery)
                                    <form method="POST" action="{{ route('account.addresses.default', $a->id) }}">@csrf<input type="hidden" name="type" value="delivery"><button type="submit" class="btn btn-ghost btn-sm">Deliver here</button></form>
                                @endif
                                @if (!$a->is_default_billing)
                                    <form method="POST" action="{{ route('account.addresses.default', $a->id) }}">@csrf<input type="hidden" name="type" value="billing"><button type="submit" class="btn btn-ghost btn-sm">Bill here</button></form>
                                @endif
                                <button type="button" class="btn btn-ghost btn-sm" data-address-edit="{{ $a->id }}" data-update-url="{{ route('account.addresses.update', $a->id) }}">Edit</button>
                                <form method="POST" action="{{ route('account.addresses.destroy', $a->id) }}" onsubmit="return confirm('Remove this address?')">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-sm">Remove</button></form>
                            </span>
                        </div>
                        <script type="application/json" data-address-data="{{ $a->id }}">{!! json_encode($a->only(['id', 'label', 'name', 'phone', 'address', 'city', 'postcode', 'is_default_delivery', 'is_default_billing']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
                    @endforeach
                    <h3 style="font-size:1rem;margin:1.25rem 0 0.75rem;" data-address-form-title>Add a new address</h3>
                    <form method="POST" action="{{ route('account.addresses.store') }}" data-address-form>
                        @csrf
                        <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="ad-label">Label</label>
                                <input id="ad-label" type="text" name="label" required maxlength="50" placeholder="Home, Work…" value="{{ old('label') }}" data-address-field="label">
                            </div>
                            <div class="form-group">
                                <label for="ad-name">Full name</label>
                                <input id="ad-name" type="text" name="name" required maxlength="100" value="{{ old('name', $user->name) }}" data-address-field="name">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="ad-phone">Phone</label>
                            <input id="ad-phone" type="tel" name="phone" required maxlength="30" value="{{ old('phone', $user->phone) }}" data-address-field="phone">
                        </div>
                        <div class="form-group">
                            <label for="ad-address">Street address</label>
                            <input id="ad-address" type="text" name="address" required maxlength="500" value="{{ old('address') }}" data-address-field="address">
                        </div>
                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="ad-city">Town / City</label>
                                <input id="ad-city" type="text" name="city" required maxlength="100" value="{{ old('city') }}" data-address-field="city">
                            </div>
                            <div class="form-group">
                                <label for="ad-postcode">Postcode</label>
                                <input id="ad-postcode" type="text" name="postcode" required maxlength="20" value="{{ old('postcode') }}" data-address-field="postcode">
                            </div>
                        </div>
                        <div class="form-group" style="display:flex;gap:1rem;flex-wrap:wrap;">
                            <label style="font-weight:400;"><input type="checkbox" name="is_default_delivery" value="1" @checked(old('is_default_delivery', $addresses->isEmpty())) data-address-field="is_default_delivery"> Default delivery</label>
                            <label style="font-weight:400;"><input type="checkbox" name="is_default_billing" value="1" @checked(old('is_default_billing', $addresses->isEmpty())) data-address-field="is_default_billing"> Default billing</label>
                        </div>
                        @if ($errors->has('label'))
                            <p style="color:#B91C1C;font-size:13px;">Please check the highlighted address fields.</p>
                        @endif
                        <div style="display:flex;gap:.5rem;">
                            <button type="submit" class="btn btn-dark" data-address-submit>Save address</button>
                            <button type="button" class="btn btn-ghost" data-address-cancel hidden>Cancel edit</button>
                        </div>
                    </form>
                </section>

                <section class="auth-card" data-portal-panel="password" @if ($defaultTab !== 'password') hidden @endif>
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
                                <label for="pw-new">New password <span class="text-muted">(6 digits)</span></label>
                                <input id="pw-new" type="password" name="password" required inputmode="numeric" maxlength="6" autocomplete="new-password">
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
        if (!root) return;
        var panels = root.querySelectorAll('[data-portal-panel]');
        var links = document.querySelectorAll('[data-portal-tab]');
        var valid = ['orders', 'lists', 'loyalty', 'addresses', 'details', 'password'];

        function show(name) {
            if (valid.indexOf(name) === -1) name = root.dataset.default || 'orders';
            panels.forEach(function (p) { p.hidden = p.dataset.portalPanel !== name; });
            links.forEach(function (l) {
                var on = l.dataset.portalTab === name;
                l.classList.toggle('active', on);
                if (on) l.setAttribute('aria-current', 'true');
                else l.removeAttribute('aria-current');
            });
        }

        links.forEach(function (l) {
            if (l._egfBound) return;
            l._egfBound = true;
            /* Instant switch for snappy tabs — the @spa href still SPA-navigates
               so refresh / back-button / deep-links render the same tab server-side. */
            l.addEventListener('click', function () { show(l.dataset.portalTab); });
        });
        show(root.dataset.default || 'orders');
    }
    document.addEventListener('spa:loaded', egfPortalInit);
    egfPortalInit();
    /* Address book add/edit — fills the shared form from the card's JSON. */
    (function egfAddressForm() {
        var form = document.querySelector('[data-address-form]');
        if (!form || form._egfBound) return;
        form._egfBound = true;
        var storeAction = form.getAttribute('action');
        var title = document.querySelector('[data-address-form-title]');
        var submit = form.querySelector('[data-address-submit]');
        var cancel = form.querySelector('[data-address-cancel]');
        var editId = form.querySelector('input[name="_edit_id"]');
        function field(name) { return form.querySelector('[data-address-field="' + name + '"]'); }
        function reset() {
            form.setAttribute('action', storeAction);
            if (editId) editId.value = '';
            if (title) title.textContent = 'Add a new address';
            if (submit) submit.textContent = 'Save address';
            if (cancel) cancel.hidden = true;
        }
        document.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('[data-address-edit]') : null;
            if (!btn) return;
            var dataEl = document.querySelector('[data-address-data="' + btn.getAttribute('data-address-edit') + '"]');
            if (!dataEl) return;
            var d = JSON.parse(dataEl.textContent);
            form.setAttribute('action', btn.getAttribute('data-update-url'));
            if (editId) editId.value = d.id;
            ['label', 'name', 'phone', 'address', 'city', 'postcode'].forEach(function (k) {
                if (field(k)) field(k).value = d[k] || '';
            });
            if (field('is_default_delivery')) field('is_default_delivery').checked = !!d.is_default_delivery;
            if (field('is_default_billing')) field('is_default_billing').checked = !!d.is_default_billing;
            if (title) title.textContent = 'Edit address: ' + (d.label || '');
            if (submit) submit.textContent = 'Update address';
            if (cancel) cancel.hidden = false;
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        if (cancel) cancel.addEventListener('click', function () {
            form.reset();
            reset();
        });
        if (editId && editId.value) {
            var btn = document.querySelector('[data-address-edit="' + editId.value + '"]');
            if (btn) {
                form.setAttribute('action', btn.getAttribute('data-update-url'));
                if (title) title.textContent = 'Edit address';
                if (submit) submit.textContent = 'Update address';
                if (cancel) cancel.hidden = false;
            }
        }
    })();
</script>
@endsection
