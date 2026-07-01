<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

test('a client can authenticate against the client guard with correct credentials', function () {
    $client = Client::factory()->create(['password' => Hash::make('correct-password')]);

    $result = Auth::guard('client')->attempt([
        'email' => $client->email,
        'password' => 'correct-password',
    ]);

    expect($result)->toBeTrue()
        ->and(Auth::guard('client')->id())->toBe($client->id);
});

test('a client cannot authenticate against the client guard with an incorrect password', function () {
    $client = Client::factory()->create(['password' => Hash::make('correct-password')]);

    $result = Auth::guard('client')->attempt([
        'email' => $client->email,
        'password' => 'wrong-password',
    ]);

    expect($result)->toBeFalse()
        ->and(Auth::guard('client')->check())->toBeFalse();
});

test('the client guard and web guard are independent', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web');

    expect(Auth::guard('web')->check())->toBeTrue()
        ->and(Auth::guard('client')->check())->toBeFalse();
});

test('a client can create a sanctum api token', function () {
    $client = Client::factory()->create();

    $token = $client->createToken('mobile-app');

    expect($token->plainTextToken)->toBeString()
        ->and($client->tokens()->count())->toBe(1);
});

test('the clients password broker resolves to the Client provider', function () {
    $client = Client::factory()->create();

    $status = Password::broker('clients')->sendResetLink(['email' => $client->email]);

    expect($status)->toBe(Password::RESET_LINK_SENT);
});
