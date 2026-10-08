<?php

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('footer signup stores the subscriber', function () {
    $this->postJson(route('newsletter.subscribe'), ['email' => 'Shopper@Example.com'])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(Subscriber::where('email', 'shopper@example.com')->firstOrFail()->source)->toBe('footer');
    $this->get('/')->assertOk()->assertSee('Fresh deals in your inbox', false);
});

test('duplicate signup is idempotent, not an error', function () {
    $this->postJson(route('newsletter.subscribe'), ['email' => 'repeat@example.com'])->assertOk();
    $this->postJson(route('newsletter.subscribe'), ['email' => 'repeat@example.com', 'source' => 'modal'])
        ->assertOk()
        ->assertJsonPath('message', 'That email is already subscribed.');

    expect(Subscriber::where('email', 'repeat@example.com')->count())->toBe(1);
});

test('invalid email is rejected on web and api', function () {
    $this->postJson(route('newsletter.subscribe'), ['email' => 'not-an-email'])->assertStatus(422);
    $this->postJson('/api/newsletter', ['email' => ''])->assertStatus(422);
    $this->postJson('/api/newsletter', ['email' => 'app@example.com', 'source' => 'app'])
        ->assertOk()
        ->assertJson(['success' => true]);
    expect(Subscriber::where('email', 'app@example.com')->firstOrFail()->source)->toBe('app');
});

test('admin manages the subscriber list', function () {
    Subscriber::create(['email' => 'one@example.com', 'source' => 'footer']);
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'user_type' => 1]);

    $this->get(route('subscribers.index'))->assertRedirect(route('login'));

    $this->actingAs($admin)->get(route('subscribers.index'))->assertOk()->assertSee('one@example.com', false);

    $this->actingAs($admin)->post(route('subscribers.toggleStatus'), ['id' => Subscriber::first()->id])
        ->assertRedirect(route('subscribers.index'));
    expect(Subscriber::first()->is_active)->toBeFalse();

    $this->actingAs($admin)->delete(route('subscribers.delete', Subscriber::first()->id))
        ->assertRedirect(route('subscribers.index'));
    expect(Subscriber::count())->toBe(0);
});
