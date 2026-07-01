<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;

function checkoutCompletedPayload(string $eventId, string $sessionId, string $paymentIntentId, int $amountTotalCents): array
{
    return [
        'id' => $eventId,
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => $sessionId,
                'object' => 'checkout.session',
                'payment_intent' => $paymentIntentId,
                'amount_total' => $amountTotalCents,
                'payment_status' => 'paid',
            ],
        ],
    ];
}

test('the webhook marks the invoice as paid and records a payment', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::Sent,
        'total' => 150,
        'stripe_checkout_session_id' => 'cs_test_123',
    ]);

    [$body, $signature] = signedStripeWebhookPayload(
        checkoutCompletedPayload('evt_1', 'cs_test_123', 'pi_test_123', 15000)
    );

    $this->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertOk();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);

    $this->assertDatabaseHas('payments', [
        'invoice_id' => $invoice->id,
        'stripe_payment_intent_id' => 'pi_test_123',
        'amount' => 150,
        'status' => 'succeeded',
    ]);
});

test('duplicate webhook deliveries do not create duplicate payments', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::Sent,
        'total' => 150,
        'stripe_checkout_session_id' => 'cs_test_123',
    ]);

    [$body, $signature] = signedStripeWebhookPayload(
        checkoutCompletedPayload('evt_1', 'cs_test_123', 'pi_test_123', 15000)
    );

    $this->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertOk();

    $this->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertOk();

    expect(Payment::where('stripe_payment_intent_id', 'pi_test_123')->count())->toBe(1);
});

test('a webhook request with an invalid signature is rejected', function () {
    $body = json_encode(checkoutCompletedPayload('evt_1', 'cs_test_123', 'pi_test_123', 15000));

    $this->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=not-a-real-signature',
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertStatus(400);

    expect(Payment::count())->toBe(0);
});

test('a webhook for an unknown checkout session is acknowledged but ignored', function () {
    [$body, $signature] = signedStripeWebhookPayload(
        checkoutCompletedPayload('evt_1', 'cs_does_not_exist', 'pi_test_123', 15000)
    );

    $this->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertOk();

    expect(Payment::count())->toBe(0);
});

test('unrelated event types are acknowledged without side effects', function () {
    [$body, $signature] = signedStripeWebhookPayload([
        'id' => 'evt_2',
        'object' => 'event',
        'type' => 'payment_intent.created',
        'data' => ['object' => ['id' => 'pi_test_999']],
    ]);

    $this->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertOk();

    expect(Payment::count())->toBe(0);
});
