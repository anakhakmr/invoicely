<?php

use App\Models\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

test('login page is displayed', function () {
    $this->get(route('client.login'))->assertOk();
});

test('a client can log in with correct credentials', function () {
    $client = Client::factory()->create(['password' => Hash::make('correct-password')]);

    livewire('pages::client.login')
        ->set('email', $client->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertRedirect(route('client.dashboard'));

    expect(auth('client')->check())->toBeTrue()
        ->and(auth('client')->id())->toBe($client->id);
});

test('a client cannot log in with an incorrect password', function () {
    $client = Client::factory()->create(['password' => Hash::make('correct-password')]);

    livewire('pages::client.login')
        ->set('email', $client->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(auth('client')->check())->toBeFalse();
});

test('unauthenticated clients are redirected to the client login page, not the staff login page', function () {
    $this->get(route('client.dashboard'))
        ->assertRedirect(route('client.login'));
});

test('a client can request a password reset link', function () {
    $client = Client::factory()->create();

    livewire('pages::client.forgot-password')
        ->set('email', $client->email)
        ->call('sendResetLink')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('client_password_reset_tokens', [
        'email' => $client->email,
    ]);
});

test('a client can reset their password with a valid token and then log in', function () {
    $client = Client::factory()->create(['password' => Hash::make('old-password')]);

    $token = Password::broker('clients')->createToken($client);

    livewire('pages::client.reset-password', ['token' => $token])
        ->set('email', $client->email)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('resetPassword')
        ->assertRedirect(route('client.login'));

    expect(Hash::check('new-password', $client->fresh()->password))->toBeTrue();
});

test('a logged in client can log out', function () {
    $client = Client::factory()->create();

    $this->actingAs($client, 'client');

    $this->post(route('client.logout'))
        ->assertRedirect(route('client.login'));

    expect(auth('client')->check())->toBeFalse();
});
