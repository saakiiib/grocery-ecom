<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShopSettingsController extends Controller
{
    public const FIELDS = [
        'delivery_min_order' => 'Minimum order for delivery (£)',
        'delivery_free_over' => 'Free delivery over (£)',
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
        'messenger_url' => 'Facebook Messenger chat link',
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
        ];

        return view('admin.shop-settings.edit', compact('settings', 'sources'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'delivery_min_order' => 'required|numeric|min:0|max:9999',
            'delivery_free_over' => 'required|numeric|min:0|max:9999',
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
}
