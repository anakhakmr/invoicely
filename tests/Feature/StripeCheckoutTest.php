<?php

use App\Actions\Stripe\CreateStripeCheckoutSession;
use App\Actions\Stripe\StripeCheckoutGateway;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Mockery\MockInterface;
use Stripe\Checkout\Session;

function mockStripeCheckoutGateway(): MockInterface
{
    $mock = Mockery::mock(StripeCheckoutGateway::class);

    app()->instance(StripeCheckoutGateway::class, $mock);

    return $mock;
}

/**
 * @param  array<string, mixed>  $values
 */
function fakeStripeSession(array $values): Session
{
    return Session::constructFrom($values);
}

test('creates a stripe checkout session from the invoice items and stores the session id', function () {
    $client = Client::factory()->create(['email' => 'jane@example.com']);
    $invoice = Invoice::factory()->create(['client_id' => $client->id, 'status' => InvoiceStatus::Sent]);
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Design work',
        'quantity' => 2,
        'unit_price' => 50,
    ]);

    mockStripeCheckoutGateway()
        ->shouldReceive('createSession')
        ->once()
        ->withArgs(fn (array $params): bool => $params['mode'] === 'payment'
            && $params['customer_email'] === 'jane@example.com'
            && $params['line_items'][0]['quantity'] === 2
            && $params['line_items'][0]['price_data']['unit_amount'] === 5000
            && $params['line_items'][0]['price_data']['product_data']['name'] === 'Design work'
            && $params['metadata']['invoice_id'] === (string) $invoice->id)
        ->andReturn(fakeStripeSession(['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.test/cs_test_123']));

    $session = app(CreateStripeCheckoutSession::class)->handle($invoice);

    expect($session->id)->toBe('cs_test_123')
        ->and($invoice->fresh()->stripe_checkout_session_id)->toBe('cs_test_123');
});

test('cannot create a checkout session for an already paid invoice', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid]);

    expect(fn () => app(CreateStripeCheckoutSession::class)->handle($invoice))
        ->toThrow(RuntimeException::class);
});

test('pay now redirects the client to the stripe checkout url', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id, 'status' => InvoiceStatus::Sent]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

    mockStripeCheckoutGateway()
        ->shouldReceive('createSession')
        ->once()
        ->andReturn(fakeStripeSession(['id' => 'cs_test_456', 'url' => 'https://checkout.stripe.test/cs_test_456']));

    $this->actingAs($client, 'client');

    livewire('pages::client.invoice', ['invoice' => $invoice])
        ->call('pay')
        ->assertRedirect('https://checkout.stripe.test/cs_test_456');
});

test('the pay now button is hidden for an already paid invoice', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id, 'status' => InvoiceStatus::Paid]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

    $this->actingAs($client, 'client');

    livewire('pages::client.invoice', ['invoice' => $invoice])
        ->assertDontSee('Pay Now');
});

test('calling pay directly on an already paid invoice is rejected even with the button hidden', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id, 'status' => InvoiceStatus::Paid]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

    $gateway = mockStripeCheckoutGateway();
    $gateway->shouldNotReceive('createSession');

    $this->actingAs($client, 'client');

    livewire('pages::client.invoice', ['invoice' => $invoice])
        ->call('pay')
        ->assertNoRedirect();

    expect($invoice->fresh()->stripe_checkout_session_id)->toBeNull();
});

test('the success page reconciles payment immediately if the webhook has not arrived yet', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => InvoiceStatus::Sent,
        'total' => 100,
        'stripe_checkout_session_id' => 'cs_test_789',
    ]);

    mockStripeCheckoutGateway()
        ->shouldReceive('retrieveSession')
        ->once()
        ->with('cs_test_789')
        ->andReturn(fakeStripeSession([
            'id' => 'cs_test_789',
            'payment_intent' => 'pi_test_789',
            'amount_total' => 10000,
            'payment_status' => 'paid',
        ]));

    $this->actingAs($client, 'client');

    $this->get(route('client.invoices.checkout.success', $invoice).'?session_id=cs_test_789')
        ->assertOk();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

    $this->assertDatabaseHas('payments', [
        'invoice_id' => $invoice->id,
        'stripe_payment_intent_id' => 'pi_test_789',
    ]);
});

test('a client cannot reach the checkout success page for another clients invoice', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $otherInvoice = Invoice::factory()->create(['client_id' => $otherClient->id]);

    $this->actingAs($client, 'client');

    $this->get(route('client.invoices.checkout.success', $otherInvoice))
        ->assertForbidden();
});
