<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\Setting;
use App\Support\MailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ShopSettingsController extends Controller
{
    public const FIELDS = [
        'announcement_enabled' => 'Announcement bar enabled (1/0)',
        'announcement_text' => 'Announcement bar text',
        'announcement_link_text' => 'Announcement bar link text',
        'announcement_link_url' => 'Announcement bar link URL',
        'promo_enabled' => 'Welcome promo modal enabled (1/0)',
        'promo_title' => 'Welcome promo title',
        'promo_subtitle' => 'Welcome promo subtitle',
        'promo_coupon' => 'Welcome promo coupon code',
        'promo_button_text' => 'Welcome promo button text',
        'promo_button_url' => 'Welcome promo button URL',
        'social_proof_enabled' => 'Recent-sales popup enabled (1/0)',
        'delivery_min_order' => 'Minimum order for delivery (£)',
        'delivery_free_over' => 'Free delivery over (£)',
        'delivery_cutoff_time' => 'Today-delivery cutoff (HH:MM)',
        'delivery_today_fee' => 'Today delivery charge (£)',
        'delivery_standard_fee' => 'Standard (next working day) charge (£)',
        'store_postcode' => 'Store postcode (delivery centre)',
        'delivery_radius_miles' => 'Delivery radius (miles)',
        'points_per_pound' => 'Loyalty points earned per £1',
        'points_value' => '£ value of one point (0.01 = 100 pts £1)',
        'points_min_redeem' => 'Minimum points per redemption',
        'stripe_publishable' => 'Stripe publishable key',
        'stripe_secret' => 'Stripe secret key',
        'stripe_webhook_secret' => 'Stripe webhook signing secret',
        'paypal_client_id' => 'PayPal client ID',
        'paypal_secret' => 'PayPal secret',
        'paypal_webhook_id' => 'PayPal webhook ID',
        'paypal_mode' => 'PayPal mode (sandbox/live)',
        'mail_host' => 'SMTP host',
        'mail_port' => 'SMTP port',
        'mail_encryption' => 'SMTP encryption (tls/ssl/none)',
        'mail_username' => 'SMTP username',
        'mail_password' => 'SMTP password',
        'mail_from_address' => 'From email address',
        'mail_from_name' => 'From name',
        'messenger_url' => 'Facebook Messenger chat link',
        'invoice_format' => 'Invoice format (a4/pos)',
        'hygiene_rating' => 'Food hygiene rating (0–5)',
        'google_rating' => 'Google rating (e.g. 4.8)',
        'google_reviews_url' => 'Google reviews link',
    ];

    public function edit()
    {
        $settings = [];
        foreach (self::FIELDS as $key => $label) {
            $settings[$key] = Setting::get($key, '');
        }
        $sources = [
            'stripe' => CheckoutController::credentialSource('stripe'),
            'paypal' => CheckoutController::credentialSource('paypal'),
            'mail' => MailSettings::source(),
        ];

        return view('admin.shop-settings.edit', compact('settings', 'sources'));
    }

    public function update(Request $request): RedirectResponse
    {
        // New promo keys default to current values so older callers/tests posting
        // only delivery+points fields keep working.
        $request->merge([
            'announcement_enabled' => $request->input('announcement_enabled', Setting::get('announcement_enabled', '0') ?? '0'),
            'promo_enabled' => $request->input('promo_enabled', Setting::get('promo_enabled', '0') ?? '0'),
            'social_proof_enabled' => $request->input('social_proof_enabled', Setting::get('social_proof_enabled', '1') ?? '1'),
            'invoice_format' => $request->input('invoice_format', Setting::get('invoice_format', 'a4') ?? 'a4'),
            'delivery_cutoff_time' => $request->input('delivery_cutoff_time', Setting::get('delivery_cutoff_time', '20:00') ?? '20:00'),
            'delivery_today_fee' => $request->input('delivery_today_fee', Setting::get('delivery_today_fee', '2.99') ?? '2.99'),
            'delivery_standard_fee' => $request->input('delivery_standard_fee', Setting::get('delivery_standard_fee', '2.99') ?? '2.99'),
            'store_postcode' => $request->input('store_postcode', Setting::get('store_postcode', '') ?? ''),
            'delivery_radius_miles' => $request->input('delivery_radius_miles', Setting::get('delivery_radius_miles', '10') ?? '10'),
        ]);
        $data = $request->validate([
            'announcement_enabled' => 'required|in:0,1',
            'announcement_text' => 'nullable|string|max:255',
            'announcement_link_text' => 'nullable|string|max:60',
            'announcement_link_url' => 'nullable|string|max:255',
            'promo_enabled' => 'required|in:0,1',
            'promo_title' => 'nullable|string|max:120',
            'promo_subtitle' => 'nullable|string|max:255',
            'promo_coupon' => 'nullable|string|max:60',
            'promo_button_text' => 'nullable|string|max:60',
            'promo_button_url' => 'nullable|string|max:255',
            'social_proof_enabled' => 'required|in:0,1',
            'invoice_format' => 'in:a4,pos',
            'delivery_min_order' => 'required|numeric|min:0|max:9999',
            'delivery_free_over' => 'required|numeric|min:0|max:9999',
            'delivery_cutoff_time' => 'required|date_format:H:i',
            'delivery_today_fee' => 'required|numeric|min:0|max:9999',
            'delivery_standard_fee' => 'required|numeric|min:0|max:9999',
            'store_postcode' => 'nullable|string|max:20|regex:/^[A-Z]{1,2}[0-9][A-Z0-9]?\s?[0-9][A-Z]{2}$/i',
            'delivery_radius_miles' => 'required|numeric|min:1|max:100',
            'points_per_pound' => 'required|numeric|min:0|max:100',
            'points_value' => 'required|numeric|min:0|max:1',
            'points_min_redeem' => 'required|integer|min:1|max:100000',
            'stripe_publishable' => 'nullable|string|max:255',
            'stripe_secret' => 'nullable|string|max:255',
            'stripe_webhook_secret' => 'nullable|string|max:255',
            'paypal_client_id' => 'nullable|string|max:255',
            'paypal_secret' => 'nullable|string|max:255',
            'paypal_webhook_id' => 'nullable|string|max:255',
            'paypal_mode' => 'required|in:sandbox,live',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_encryption' => 'nullable|in:tls,ssl,none',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:500',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'messenger_url' => 'nullable|url|max:255',
            'hygiene_rating' => 'nullable|string|max:10',
            'google_rating' => 'nullable|string|max:10',
            'google_reviews_url' => 'nullable|url|max:255',
        ]);

        foreach ($data as $key => $value) {
            Setting::put($key, $value);
        }

        return redirect()->route('shop-settings.edit')->with('status', 'Shop settings saved. Empty payment keys hide that method at checkout automatically.');
    }

    /** Send a probe email with the saved SMTP settings so the admin can verify them. */
    public function sendTestMail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'test_email' => 'required|email|max:255',
        ], [
            'test_email.required' => 'Enter an email address to send the test to',
            'test_email.email' => 'Enter a valid email address',
        ]);

        MailSettings::apply();

        try {
            Mail::to($data['test_email'])->send(new TestMail);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('shop-settings.edit')->with('error', 'Test email failed: '.$e->getMessage());
        }

        return redirect()->route('shop-settings.edit')->with('status', 'Test email sent to '.$data['test_email'].' — check that inbox (and spam folder).');
    }
}
