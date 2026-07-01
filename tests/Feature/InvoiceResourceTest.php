<?php

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('can list invoices', function () {
    $invoices = Invoice::factory()->count(3)->create();

    livewire(ListInvoices::class)
        ->assertOk()
        ->assertCanSeeTableRecords($invoices);
});

test('can filter invoices by status', function () {
    $paid = Invoice::factory()->create(['status' => InvoiceStatus::Paid]);
    $draft = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

    livewire(ListInvoices::class)
        ->filterTable('status', InvoiceStatus::Paid->value)
        ->assertCanSeeTableRecords([$paid])
        ->assertCanNotSeeTableRecords([$draft]);
});

test('can create an invoice with items and the total is computed from them', function () {
    $client = Client::factory()->create();

    livewire(CreateInvoice::class)
        ->fillForm([
            'client_id' => $client->id,
            'invoice_number' => 'INV-TEST1',
            'status' => InvoiceStatus::Draft->value,
            'due_date' => now()->addDays(14)->toDateString(),
            'items' => [
                ['description' => 'Design work', 'quantity' => 2, 'unit_price' => 100],
                ['description' => 'Development', 'quantity' => 5, 'unit_price' => 150],
            ],
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $invoice = Invoice::where('invoice_number', 'INV-TEST1')->firstOrFail();

    expect($invoice->items)->toHaveCount(2)
        ->and((float) $invoice->total)->toBe(950.0);
});

test('invoice numbers must be unique', function () {
    $existing = Invoice::factory()->create();
    $client = Client::factory()->create();

    livewire(CreateInvoice::class)
        ->fillForm([
            'client_id' => $client->id,
            'invoice_number' => $existing->invoice_number,
            'status' => InvoiceStatus::Draft->value,
            'due_date' => now()->addDays(14)->toDateString(),
            'items' => [
                ['description' => 'Design work', 'quantity' => 1, 'unit_price' => 100],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['invoice_number' => 'unique']);
});
