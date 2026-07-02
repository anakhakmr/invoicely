<?php

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Models\Client;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('can list clients', function () {
    $clients = Client::factory()->count(3)->create();

    livewire(ListClients::class)
        ->assertOk()
        ->assertCanSeeTableRecords($clients);
});

test('can create a client', function () {
    $client = Client::factory()->make();

    livewire(CreateClient::class)
        ->fillForm([
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'company' => $client->company,
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseHas('clients', [
        'email' => $client->email,
    ]);
});

test('client email must be unique', function () {
    $existing = Client::factory()->create();
    $new = Client::factory()->make();

    livewire(CreateClient::class)
        ->fillForm([
            'name' => $new->name,
            'email' => $existing->email,
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);
});

test('can edit a client', function () {
    $client = Client::factory()->create();

    livewire(EditClient::class, ['record' => $client->getRouteKey()])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertNotified();

    expect($client->fresh()->name)->toBe('Updated Name');
});
