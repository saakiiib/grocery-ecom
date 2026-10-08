<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('announcement and promo stay hidden by default', function () {
    $this->get('/')->assertOk()->assertDontSee('announce-bar', false)->assertDontSee('promo-modal', false);

    $this->getJson('/api/home')->assertOk()
        ->assertJsonPath('announcement', null)
        ->assertJsonPath('promo', null);
});

test('enabled announcement renders globally with link', function () {
    Setting::put('announcement_enabled', '1');
    Setting::put('announcement_text', 'Free delivery over £50');
    Setting::put('announcement_link_text', 'Shop offers');
    Setting::put('announcement_link_url', '/shop/offers');

    $this->get('/shop')->assertOk()
        ->assertSee('announce-bar', false)
        ->assertSee('Free delivery over £50', false)
        ->assertSee('Shop offers', false);

    $this->getJson('/api/home')->assertOk()
        ->assertJsonPath('announcement.text', 'Free delivery over £50')
        ->assertJsonPath('announcement.link_text', 'Shop offers');
});

test('enabled promo modal renders on homepage with coupon', function () {
    Setting::put('promo_enabled', '1');
    Setting::put('promo_title', 'Today fresh picks');
    Setting::put('promo_subtitle', 'Today only');
    Setting::put('promo_coupon', 'FRESH10');
    Setting::put('promo_button_text', "Shop today's deals");
    Setting::put('promo_button_url', '/shop/offers');

    $this->get('/')->assertOk()
        ->assertSee('promo-modal', false)
        ->assertSee('Today fresh picks', false)
        ->assertSee('FRESH10', false);

    $this->getJson('/api/home')->assertOk()
        ->assertJsonPath('promo.title', 'Today fresh picks')
        ->assertJsonPath('promo.coupon', 'FRESH10');
});

test('admin can save announcement and promo settings', function () {
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    $this->actingAs($admin)->post(route('shop-settings.update'), [
        'announcement_enabled' => '1',
        'announcement_text' => 'Weekend flash',
        'announcement_link_text' => 'Shop now',
        'announcement_link_url' => '/shop/offers',
        'promo_enabled' => '1',
        'promo_title' => 'Weekend deals',
        'promo_subtitle' => 'Fresh today',
        'promo_coupon' => 'WEEKEND5',
        'promo_button_text' => 'Shop deals',
        'promo_button_url' => '/shop/offers',
        'delivery_min_order' => '15',
        'delivery_free_over' => '50',
        'points_per_pound' => '1',
        'points_value' => '0.01',
        'points_min_redeem' => '100',
        'paypal_mode' => 'sandbox',
    ])->assertRedirect(route('shop-settings.edit'));

    expect(Setting::get('announcement_text'))->toBe('Weekend flash')
        ->and(Setting::get('promo_title'))->toBe('Weekend deals')
        ->and(Setting::get('promo_coupon'))->toBe('WEEKEND5');

    $this->get('/')->assertOk()->assertSee('Weekend deals', false)->assertSee('announce-bar', false);
});
