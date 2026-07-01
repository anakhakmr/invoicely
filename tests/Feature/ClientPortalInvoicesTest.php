<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;

test('a client can see their own invoices on the dashboard', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();

    $ownInvoice = Invoice::factory()->create(['client_id' => $client->id]);
    Invoice::factory()->create(['client_id' => $otherClient->id]);

    $this->actingAs($client, 'client');

    livewire('pages::client.dashboard')
        ->assertOk()
        ->assertSee($ownInvoice->invoice_number);
});

test('a client cannot see another clients invoices on the dashboard', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();

    Invoice::factory()->create(['client_id' => $client->id]);
    $otherInvoice = Invoice::factory()->create(['client_id' => $otherClient->id]);

    $this->actingAs($client, 'client');

    livewire('pages::client.dashboard')
        ->assertDontSee($otherInvoice->invoice_number);
});

test('a client can view their own invoice detail', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'description' => 'Design work']);

    $this->actingAs($client, 'client');

    livewire('pages::client.invoice', ['invoice' => $invoice])
        ->assertOk()
        ->assertSee($invoice->invoice_number)
        ->assertSee('Design work');
});

test('a client cannot view another clients invoice', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $otherInvoice = Invoice::factory()->create(['client_id' => $otherClient->id]);

    $this->actingAs($client, 'client');

    livewire('pages::client.invoice', ['invoice' => $otherInvoice])
        ->assertForbidden();
});

test('guests cannot view an invoice', function () {
    $invoice = Invoice::factory()->create();

    $this->get(route('client.invoices.show', $invoice))
        ->assertRedirect(route('client.login'));
});
