<?php

use App\Models\CompanyDetails;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function contentAdmin(): User
{
    return User::create(['name' => 'Admin', 'email' => 'content-admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1, 'status' => 1]);
}

test('admin can edit content pages and storefront reflects them', function () {
    $admin = contentAdmin();

    $this->actingAs($admin)->get(route('admin.content.about'))->assertOk();
    $this->actingAs($admin)->get(route('admin.content.privacy'))->assertOk();
    $this->actingAs($admin)->get(route('admin.content.terms'))->assertOk();
    $this->actingAs($admin)->get(route('admin.content.refund'))->assertOk();

    $this->actingAs($admin)->post(route('admin.content.about.update'), ['about_us' => '<p>Our fresh story</p>'])->assertRedirect();
    $this->actingAs($admin)->post(route('admin.content.privacy.update'), ['privacy_policy' => '<p>Privacy body here</p>'])->assertRedirect();
    $this->actingAs($admin)->post(route('admin.content.terms.update'), ['terms_and_conditions' => '<p>Terms body here</p>'])->assertRedirect();
    $this->actingAs($admin)->post(route('admin.content.refund.update'), ['refund_policy' => '<p>Refund body here</p>'])->assertRedirect();

    $details = CompanyDetails::firstOrFail();
    expect($details->about_us)->toContain('Our fresh story')
        ->and($details->privacy_policy)->toContain('Privacy body here')
        ->and($details->terms_and_conditions)->toContain('Terms body here')
        ->and($details->refund_policy)->toContain('Refund body here');

    expect($this->get('/about')->assertOk()->getContent())->toContain('Our fresh story');
    expect($this->get('/privacy-policy')->assertOk()->getContent())->toContain('Privacy body here');
    expect($this->get('/terms-of-service')->assertOk()->getContent())->toContain('Terms body here');
    expect($this->get('/refund-policy')->assertOk()->getContent())->toContain('Refund body here');
});

test('content pages require validation', function () {
    $admin = contentAdmin();

    $this->actingAs($admin)->post(route('admin.content.about.update'), ['about_us' => ''])->assertSessionHasErrors('about_us');
    $this->actingAs($admin)->post(route('admin.content.refund.update'), ['refund_policy' => ''])->assertSessionHasErrors('refund_policy');
});
