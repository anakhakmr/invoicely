<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Filament\Widgets\RecentPayments;
use App\Filament\Widgets\StatsOverview;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('stats overview shows total revenue and outstanding invoices', function () {
    $paidInvoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid, 'total' => 500]);
    Payment::factory()->create(['invoice_id' => $paidInvoice->id, 'amount' => 100, 'status' => PaymentStatus::Succeeded]);
    Payment::factory()->create(['invoice_id' => $paidInvoice->id, 'amount' => 50, 'status' => PaymentStatus::Succeeded]);
    Payment::factory()->create(['invoice_id' => $paidInvoice->id, 'amount' => 999, 'status' => PaymentStatus::Failed]);

    Invoice::factory()->create(['status' => InvoiceStatus::Sent, 'total' => 200]);

    livewire(StatsOverview::class)
        ->assertOk()
        ->assertSee('150.00')
        ->assertSee('200.00');
});

test('recent payments widget lists the latest payments', function () {
    $payment = Payment::factory()->create();

    livewire(RecentPayments::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$payment]);
});
