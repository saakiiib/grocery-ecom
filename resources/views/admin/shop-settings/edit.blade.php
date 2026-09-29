@extends('admin.pages.master')
@section('title', 'Shop Settings')

@section('content')

    <div class="container-fluid">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">Shop Settings <small class="text-muted">— delivery rules and payment keys</small></h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('shop-settings.update') }}">
                            @csrf
                            <h6 class="mb-3">Delivery rules</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Minimum order for delivery (£)</label>
                                    <input type="number" name="delivery_min_order" class="form-control" required step="0.01" min="0" value="{{ old('delivery_min_order', $settings['delivery_min_order']) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Free delivery over (£)</label>
                                    <input type="number" name="delivery_free_over" class="form-control" required step="0.01" min="0" value="{{ old('delivery_free_over', $settings['delivery_free_over']) }}">
                                </div>
                            </div>

                            <h6 class="mb-3">Stripe (card payments)
                                @if ($sources['stripe'])
                                    <span class="badge bg-success">Live via {{ $sources['stripe'] === '.env' ? '.env' : 'shop settings' }}</span>
                                @else
                                    <span class="badge bg-danger">Not configured — card button hidden at checkout</span>
                                @endif
                            </h6>
                            <div class="row g-3 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">Publishable key <small class="text-muted">(fallback — .env wins)</small></label>
                                    <input type="text" name="stripe_publishable" class="form-control" maxlength="255" value="{{ old('stripe_publishable', $settings['stripe_publishable']) }}" placeholder="pk_test_…">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Secret key <small class="text-muted">(fallback — .env wins)</small></label>
                                    <input type="password" name="stripe_secret" class="form-control" maxlength="255" value="{{ old('stripe_secret', $settings['stripe_secret']) }}" placeholder="sk_test_…" autocomplete="new-password">
                                </div>
                            </div>
                            <p class="text-muted mb-4"><small>Recommended: put <code>STRIPE_PUBLISHABLE</code> / <code>STRIPE_SECRET</code> in <code>.env</code>. These fields are only used when <code>.env</code> is empty.</small></p>

                            <h6 class="mb-3">PayPal
                                @if ($sources['paypal'])
                                    <span class="badge bg-success">Live via {{ $sources['paypal'] === '.env' ? '.env' : 'shop settings' }}</span>
                                @else
                                    <span class="badge bg-danger">Not configured — PayPal button hidden at checkout</span>
                                @endif
                            </h6>
                            <div class="row g-3 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label">Client ID <small class="text-muted">(fallback — .env wins)</small></label>
                                    <input type="text" name="paypal_client_id" class="form-control" maxlength="255" value="{{ old('paypal_client_id', $settings['paypal_client_id']) }}">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Secret <small class="text-muted">(fallback — .env wins)</small></label>
                                    <input type="password" name="paypal_secret" class="form-control" maxlength="255" value="{{ old('paypal_secret', $settings['paypal_secret']) }}" autocomplete="new-password">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Mode</label>
                                    <select name="paypal_mode" class="form-control">
                                        <option value="sandbox" {{ old('paypal_mode', $settings['paypal_mode']) === 'sandbox' ? 'selected' : '' }}>Sandbox (test)</option>
                                        <option value="live" {{ old('paypal_mode', $settings['paypal_mode']) === 'live' ? 'selected' : '' }}>Live</option>
                                    </select>
                                </div>
                            </div>
                            <p class="text-muted mb-4"><small>Recommended: put <code>PAYPAL_CLIENT_ID</code> / <code>PAYPAL_SECRET</code> / <code>PAYPAL_MODE</code> in <code>.env</code>. These fields are only used when <code>.env</code> is empty.</small></p>

                            <button type="submit" class="btn btn-primary">Save settings</button>
                            <p class="text-muted mt-2 mb-0"><small>Leave a gateway's keys empty and that button simply never appears at checkout — the shop keeps selling on cash on delivery.</small></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
