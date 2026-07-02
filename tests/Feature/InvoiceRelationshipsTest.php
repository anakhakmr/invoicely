<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;

test('a client has many invoices', function () {
    $client = Client::factory()->create();
    $invoices = Invoice::factory()->count(2)->create(['client_id' => $client->id]);

    expect($client->invoices)->toHaveCount(2)
        ->and($client->invoices->pluck('id')->sort()->values()->all())
        ->toEqual($invoices->pluck('id')->sort()->values()->all());
});

test('an invoice belongs to a client and has many items and payments', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id]);
    $items = InvoiceItem::factory()->count(2)->create(['invoice_id' => $invoice->id]);
    $payment = Payment::factory()->create(['invoice_id' => $invoice->id]);

    expect($invoice->client->is($client))->toBeTrue()
        ->and($invoice->items)->toHaveCount(2)
        ->and($invoice->items->pluck('id')->sort()->values()->all())
        ->toEqual($items->pluck('id')->sort()->values()->all())
        ->and($invoice->payments->pluck('id')->all())
        ->toEqual([$payment->id]);
});

test('an invoice item and a payment belong to an invoice', function () {
    $invoice = Invoice::factory()->create();
    $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);
    $payment = Payment::factory()->create(['invoice_id' => $invoice->id]);

    expect($item->invoice->is($invoice))->toBeTrue()
        ->and($payment->invoice->is($invoice))->toBeTrue();
});

test('invoice status casts to the InvoiceStatus enum', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid]);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

test('payment status casts to the PaymentStatus enum', function () {
    $payment = Payment::factory()->create(['status' => PaymentStatus::Succeeded]);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded);
});
