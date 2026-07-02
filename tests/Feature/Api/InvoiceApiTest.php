<?php

use App\Actions\Stripe\StripeCheckoutGateway;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Laravel\Sanctum\Sanctum;
use Stripe\Checkout\Session;

test('unauthenticated requests are rejected', function () {
    $this->getJson('/api/invoices')->assertUnauthorized();
});

test('a client can list only their own invoices', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();

    $ownInvoice = Invoice::factory()->create(['client_id' => $client->id]);
    Invoice::factory()->create(['client_id' => $otherClient->id]);

    Sanctum::actingAs($client);

    $response = $this->getJson('/api/invoices')->assertOk();

    $response->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownInvoice->id);
});

test('a client can view their own invoice with items', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'description' => 'Design work']);

    Sanctum::actingAs($client);

    $this->getJson("/api/invoices/{$invoice->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $invoice->id)
        ->assertJsonPath('data.items.0.description', 'Design work');
});

test('a client cannot view another clients invoice', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $otherInvoice = Invoice::factory()->create(['client_id' => $otherClient->id]);

    Sanctum::actingAs($client);

    $this->getJson("/api/invoices/{$otherInvoice->id}")->assertForbidden();
});

test('a client can start checkout for their own invoice', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id, 'status' => InvoiceStatus::Sent]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

    $gateway = Mockery::mock(StripeCheckoutGateway::class);
    $gateway->shouldReceive('createSession')
        ->once()
        ->andReturn(Session::constructFrom(['id' => 'cs_test_api', 'url' => 'https://checkout.stripe.test/cs_test_api']));
    app()->instance(StripeCheckoutGateway::class, $gateway);

    Sanctum::actingAs($client);

    $this->postJson("/api/invoices/{$invoice->id}/checkout")
        ->assertOk()
        ->assertJson([
            'checkout_url' => 'https://checkout.stripe.test/cs_test_api',
            'session_id' => 'cs_test_api',
        ]);
});

test('a client cannot start checkout for another clients invoice', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $otherInvoice = Invoice::factory()->create(['client_id' => $otherClient->id, 'status' => InvoiceStatus::Sent]);

    Sanctum::actingAs($client);

    $this->postJson("/api/invoices/{$otherInvoice->id}/checkout")->assertForbidden();
});

test('checkout is rejected for an already paid invoice', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id, 'status' => InvoiceStatus::Paid]);

    Sanctum::actingAs($client);

    $this->postJson("/api/invoices/{$invoice->id}/checkout")
        ->assertStatus(422);
});

test('a real bearer token authenticates api requests end to end', function () {
    $client = Client::factory()->create();
    Invoice::factory()->create(['client_id' => $client->id]);

    $token = $client->createToken('mobile-app')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/invoices')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
