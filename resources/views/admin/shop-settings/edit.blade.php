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
                            <h6 class="mb-3">Announcement bar</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Enabled</label>
                                    <select name="announcement_enabled" class="form-control">
                                        <option value="0" {{ old('announcement_enabled', $settings['announcement_enabled'] ?? '0') === '0' ? 'selected' : '' }}>Hidden</option>
                                        <option value="1" {{ old('announcement_enabled', $settings['announcement_enabled'] ?? '0') === '1' ? 'selected' : '' }}>Visible</option>
                                    </select>
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">Bar text</label>
                                    <input type="text" name="announcement_text" class="form-control" maxlength="255" value="{{ old('announcement_text', $settings['announcement_text'] ?? '') }}" placeholder="Free delivery over £50 — this weekend only">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Link text (optional)</label>
                                    <input type="text" name="announcement_link_text" class="form-control" maxlength="60" value="{{ old('announcement_link_text', $settings['announcement_link_text'] ?? '') }}" placeholder="Shop offers">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Link URL (optional)</label>
                                    <input type="text" name="announcement_link_url" class="form-control" maxlength="255" value="{{ old('announcement_link_url', $settings['announcement_link_url'] ?? '') }}" placeholder="/shop/offers">
                                </div>
                            </div>

                            <h6 class="mb-3">Welcome promo modal (homepage)</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Enabled</label>
                                    <select name="promo_enabled" class="form-control">
                                        <option value="0" {{ old('promo_enabled', $settings['promo_enabled'] ?? '0') === '0' ? 'selected' : '' }}>Hidden</option>
                                        <option value="1" {{ old('promo_enabled', $settings['promo_enabled'] ?? '0') === '1' ? 'selected' : '' }}>Visible</option>
                                    </select>
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="promo_title" class="form-control" maxlength="120" value="{{ old('promo_title', $settings['promo_title'] ?? '') }}" placeholder="Today's fresh picks">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Subtitle</label>
                                    <input type="text" name="promo_subtitle" class="form-control" maxlength="255" value="{{ old('promo_subtitle', $settings['promo_subtitle'] ?? '') }}" placeholder="Hand-picked deals, today only">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Coupon code (optional)</label>
                                    <input type="text" name="promo_coupon" class="form-control" maxlength="60" value="{{ old('promo_coupon', $settings['promo_coupon'] ?? '') }}" placeholder="FRESH10">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Button text (optional)</label>
                                    <input type="text" name="promo_button_text" class="form-control" maxlength="60" value="{{ old('promo_button_text', $settings['promo_button_text'] ?? '') }}" placeholder="Shop today's deals">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Button URL (optional)</label>
                                    <input type="text" name="promo_button_url" class="form-control" maxlength="255" value="{{ old('promo_button_url', $settings['promo_button_url'] ?? '') }}" placeholder="/shop/offers">
                                </div>
                            </div>
                            <p class="text-muted mb-4"><small>The modal shows today's offer products automatically, once per visitor per day.</small></p>

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

                            <h6 class="mb-3">Loyalty points</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Points earned per £1 of goods</label>
                                    <input type="number" name="points_per_pound" class="form-control" required step="0.1" min="0" max="100" value="{{ old('points_per_pound', $settings['points_per_pound']) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">£ value of one point</label>
                                    <input type="number" name="points_value" class="form-control" required step="0.001" min="0" max="1" value="{{ old('points_value', $settings['points_value']) }}">
                                    <small class="text-muted">0.01 means 100 points = £1 off.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Minimum points per redemption</label>
                                    <input type="number" name="points_min_redeem" class="form-control" required step="1" min="1" value="{{ old('points_min_redeem', $settings['points_min_redeem']) }}">
                                </div>
                            </div>
                            <p class="text-muted mb-4"><small>Points are awarded when an order is Delivered (registered shoppers only) and refunded automatically if the order is Cancelled.</small></p>

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
                                <div class="col-md-6">
                                    <label class="form-label">Webhook signing secret <small class="text-muted">(fallback — .env STRIPE_WEBHOOK_SECRET wins)</small></label>
                                    <input type="password" name="stripe_webhook_secret" class="form-control" maxlength="255" value="{{ old('stripe_webhook_secret', $settings['stripe_webhook_secret']) }}" placeholder="whsec_…" autocomplete="new-password">
                                </div>
                            </div>
                            <p class="text-muted mb-4"><small>Recommended: put <code>STRIPE_PUBLISHABLE</code> / <code>STRIPE_SECRET</code> in <code>.env</code>. These fields are only used when <code>.env</code> is empty.</small></p>
                            <p class="text-muted mb-4"><small>Webhook endpoint: <code>{{ url('/webhooks/stripe') }}</code> — paste it into the Stripe Dashboard → Developers → Webhooks, then copy the signing secret here.</small></p>

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
                                <div class="col-md-6">
                                    <label class="form-label">Webhook ID <small class="text-muted">(fallback — .env PAYPAL_WEBHOOK_ID wins)</small></label>
                                    <input type="text" name="paypal_webhook_id" class="form-control" maxlength="255" value="{{ old('paypal_webhook_id', $settings['paypal_webhook_id']) }}">
                                </div>
                            </div>
                            <p class="text-muted mb-4"><small>Recommended: put <code>PAYPAL_CLIENT_ID</code> / <code>PAYPAL_SECRET</code> / <code>PAYPAL_MODE</code> in <code>.env</code>. These fields are only used when <code>.env</code> is empty.</small></p>
                            <p class="text-muted mb-4"><small>Webhook endpoint: <code>{{ url('/webhooks/paypal') }}</code> — subscribe it to Payment Capture Completed / Denied in the PayPal Developer Dashboard, then copy the webhook ID here.</small></p>

                            <h6 class="mb-3">Ratings &amp; chat</h6>
                            <div class="row g-3 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">Messenger chat link</label>
                                    <input type="url" name="messenger_url" class="form-control" maxlength="255" value="{{ old('messenger_url', $settings['messenger_url']) }}" placeholder="https://m.me/yourpage">
                                    <small class="text-muted">Shows as a floating chat button. Leave empty to hide it.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Google reviews link</label>
                                    <input type="url" name="google_reviews_url" class="form-control" maxlength="255" value="{{ old('google_reviews_url', $settings['google_reviews_url']) }}" placeholder="https://g.page/…">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Food hygiene rating</label>
                                    <input type="text" name="hygiene_rating" class="form-control" maxlength="10" value="{{ old('hygiene_rating', $settings['hygiene_rating']) }}" placeholder="5">
                                    <small class="text-muted">Shown as “Food Hygiene Rating: X/5”. Leave empty to hide.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Google rating</label>
                                    <input type="text" name="google_rating" class="form-control" maxlength="10" value="{{ old('google_rating', $settings['google_rating']) }}" placeholder="4.8">
                                </div>
                            </div>
                            <p class="text-muted mb-4"><small>Empty ratings stay hidden on the homepage until you fill them in.</small></p>

                            <button type="submit" class="btn btn-primary">Save settings</button>
                            <p class="text-muted mt-2 mb-0"><small>Leave a gateway's keys empty and that button simply never appears at checkout — the shop keeps selling on cash on delivery.</small></p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
