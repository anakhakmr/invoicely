<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Database\QueryException;

test('client, invoice, invoice item, and payment factories create valid rows', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id]);
    $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);
    $payment = Payment::factory()->create(['invoice_id' => $invoice->id]);

    expect(Client::count())->toBe(1)
        ->and(Invoice::count())->toBe(1)
        ->and(InvoiceItem::count())->toBe(1)
        ->and(Payment::count())->toBe(1)
        ->and($invoice->client_id)->toBe($client->id)
        ->and($item->invoice_id)->toBe($invoice->id)
        ->and($payment->invoice_id)->toBe($invoice->id);
});

test('client emails must be unique', function () {
    $client = Client::factory()->create();

    expect(fn () => Client::factory()->create(['email' => $client->email]))
        ->toThrow(QueryException::class);
});

test('invoice numbers must be unique', function () {
    $invoice = Invoice::factory()->create();

    expect(fn () => Invoice::factory()->create(['invoice_number' => $invoice->invoice_number]))
        ->toThrow(QueryException::class);
});

test('stripe payment intent ids must be unique', function () {
    $payment = Payment::factory()->create(['stripe_payment_intent_id' => 'pi_123']);

    expect(fn () => Payment::factory()->create(['stripe_payment_intent_id' => 'pi_123']))
        ->toThrow(QueryException::class);
});

test('a client cannot be deleted while it has invoices', function () {
    $client = Client::factory()->create();
    Invoice::factory()->create(['client_id' => $client->id]);

    expect(fn () => $client->delete())->toThrow(QueryException::class);
});

test('deleting an invoice cascades to its invoice items', function () {
    $invoice = Invoice::factory()->create();
    $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

    $invoice->delete();

    expect(InvoiceItem::find($item->id))->toBeNull();
});

test('an invoice cannot be deleted while it has payments', function () {
    $invoice = Invoice::factory()->create();
    Payment::factory()->create(['invoice_id' => $invoice->id]);

    expect(fn () => $invoice->delete())->toThrow(QueryException::class);
});
