<?php

use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('can list payments', function () {
    $payments = Payment::factory()->count(3)->create();

    livewire(ListPayments::class)
        ->assertOk()
        ->assertCanSeeTableRecords($payments);
});

test('can view a payment', function () {
    $payment = Payment::factory()->create();

    livewire(ViewPayment::class, ['record' => $payment->getRouteKey()])
        ->assertOk();
});

test('payments cannot be created, edited, or deleted', function () {
    $payment = Payment::factory()->create();

    expect(PaymentResource::canCreate())->toBeFalse()
        ->and(PaymentResource::canEdit($payment))->toBeFalse()
        ->and(PaymentResource::canDelete($payment))->toBeFalse()
        ->and(PaymentResource::canDeleteAny())->toBeFalse();
});

test('the payment resource has no create or edit routes', function () {
    expect(Route::has('filament.admin.resources.payments.create'))->toBeFalse()
        ->and(Route::has('filament.admin.resources.payments.edit'))->toBeFalse();
});
