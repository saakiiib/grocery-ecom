@extends('admin.pages.master')
@section('title', 'Shop Settings')

@section('content')

    <div class="container-fluid">

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

                            <h6 class="mb-3">Social proof popup</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Recent-sales popup</label>
                                    <select name="social_proof_enabled" class="form-control">
                                        <option value="0" {{ old('social_proof_enabled', $settings['social_proof_enabled'] ?? '1') === '0' ? 'selected' : '' }}>Hidden</option>
                                        <option value="1" {{ old('social_proof_enabled', $settings['social_proof_enabled'] ?? '1') === '1' ? 'selected' : '' }}>Visible</option>
                                    </select>
                                </div>
                                <div class="col-md-9">
                                    <p class="text-muted mt-4"><small>Shows anonymised recent orders ("Rahim from Leeds bought Lamb Leg") — max 3 per page view, never from cancelled orders.</small></p>
                                </div>
                            </div>

                            <h6 class="mb-3">Delivery rules</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Today-delivery cutoff</label>
                                    <input type="time" name="delivery_cutoff_time" class="form-control" required value="{{ old('delivery_cutoff_time', $settings['delivery_cutoff_time'] ?? '20:00') }}">
                                    <small class="text-muted">Last time a Today order can be placed. After this, only Next working day shows.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Today delivery charge (£)</label>
                                    <input type="number" name="delivery_today_fee" class="form-control" required step="0.01" min="0" value="{{ old('delivery_today_fee', $settings['delivery_today_fee'] ?? '2.99') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Standard charge — next working day (£)</label>
                                    <input type="number" name="delivery_standard_fee" class="form-control" required step="0.01" min="0" value="{{ old('delivery_standard_fee', $settings['delivery_standard_fee'] ?? '2.99') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Minimum order for delivery (£)</label>
                                    <input type="number" name="delivery_min_order" class="form-control" required step="0.01" min="0" value="{{ old('delivery_min_order', $settings['delivery_min_order']) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Free delivery over (£)</label>
                                    <input type="number" name="delivery_free_over" class="form-control" required step="0.01" min="0" value="{{ old('delivery_free_over', $settings['delivery_free_over']) }}">
                                    <small class="text-muted">Frees both Today and Standard.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Store postcode</label>
                                    <input type="text" name="store_postcode" class="form-control" maxlength="20" value="{{ old('store_postcode', $settings['store_postcode'] ?? '') }}" placeholder="e.g. LS1 1AA">
                                    <small class="text-muted">Delivery centre. Leave blank for open delivery (everywhere).</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Delivery radius (miles)</label>
                                    <input type="number" name="delivery_radius_miles" class="form-control" required step="0.5" min="1" max="100" value="{{ old('delivery_radius_miles', $settings['delivery_radius_miles'] ?? '10') }}">
                                    <small class="text-muted">Straight-line miles from the store postcode, looked up automatically.</small>
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

                            <h6 class="mb-3">Invoice</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Invoice format</label>
                                    <select name="invoice_format" class="form-control">
                                        <option value="a4" @selected(old('invoice_format', $settings['invoice_format'] ?? 'a4') === 'a4')>A4 — full professional invoice</option>
                                        <option value="pos" @selected(old('invoice_format', $settings['invoice_format'] ?? 'a4') === 'pos')>POS — 80mm thermal till receipt</option>
                                    </select>
                                    <small class="text-muted">Applies to the invoice page, the print button and the PDF download.</small>
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

                            <h6 class="mb-3">Email (SMTP)
                                @if ($sources['mail'])
                                    <span class="badge bg-success">Live via {{ $sources['mail'] === '.env' ? '.env' : 'shop settings' }}</span>
                                @else
                                    <span class="badge bg-danger">Not configured — password resets and order emails only reach the log</span>
                                @endif
                            </h6>
                            <div class="row g-3 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">SMTP host <small class="text-muted">(fallback — MAIL_HOST wins when set)</small></label>
                                    <input type="text" name="mail_host" class="form-control" maxlength="255" value="{{ old('mail_host', $settings['mail_host']) }}" placeholder="smtp.gmail.com">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Port</label>
                                    <input type="number" name="mail_port" class="form-control" min="1" max="65535" value="{{ old('mail_port', $settings['mail_port'] ?? '587') }}" placeholder="587">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Encryption</label>
                                    <select name="mail_encryption" class="form-control">
                                        <option value="tls" {{ old('mail_encryption', $settings['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS (port 587)</option>
                                        <option value="ssl" {{ old('mail_encryption', $settings['mail_encryption'] ?? 'tls') === 'ssl' ? 'selected' : '' }}>SSL (port 465)</option>
                                        <option value="none" {{ old('mail_encryption', $settings['mail_encryption'] ?? 'tls') === 'none' ? 'selected' : '' }}>None (local only)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP username <small class="text-muted">(fallback — MAIL_USERNAME wins)</small></label>
                                    <input type="text" name="mail_username" class="form-control" maxlength="255" value="{{ old('mail_username', $settings['mail_username']) }}" placeholder="you@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP password <small class="text-muted">(fallback — MAIL_PASSWORD wins)</small></label>
                                    <input type="password" name="mail_password" class="form-control" maxlength="500" value="{{ old('mail_password', $settings['mail_password']) }}" placeholder="app password" autocomplete="new-password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">From email address</label>
                                    <input type="email" name="mail_from_address" class="form-control" maxlength="255" value="{{ old('mail_from_address', $settings['mail_from_address']) }}" placeholder="orders@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">From name</label>
                                    <input type="text" name="mail_from_name" class="form-control" maxlength="255" value="{{ old('mail_from_name', $settings['mail_from_name']) }}" placeholder="Alam Mini Market">
                                </div>
                            </div>
                            <p class="text-muted mb-4"><small>Gmail needs an <strong>app password</strong> (Google Account → Security → 2-Step Verification → App passwords), not your login password. Leave everything empty to keep logging mail instead of sending it.</small></p>

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
                        <hr class="my-4">
                        <h6 class="mb-3">Test this email setup</h6>
                        <form method="POST" action="{{ route('shop-settings.mail-test') }}" class="row g-3 align-items-end">
                            @csrf
                            <div class="col-md-8">
                                <label class="form-label">Send a test email to</label>
                                <input type="email" name="test_email" class="form-control" maxlength="255" required placeholder="you@example.com" value="{{ old('test_email', auth()->user()->email ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-secondary">Send test email</button>
                            </div>
                        </form>
                        <p class="text-muted mt-2 mb-0"><small>Sends with the saved settings above — save first, then test. A success toast means the inbox (or spam) should have it.</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
