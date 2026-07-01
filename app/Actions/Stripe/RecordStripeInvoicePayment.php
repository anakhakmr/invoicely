<?php

namespace App\Actions\Stripe;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class RecordStripeInvoicePayment
{
    /**
     * Idempotently record a completed Stripe Checkout payment against its invoice.
     *
     * Safe to call more than once for the same checkout session (e.g. once from
     * the webhook and once from the success-page reconciliation check) - a
     * second call is a no-op because both the existence check and the
     * database's unique constraint on stripe_payment_intent_id prevent
     * duplicate Payment rows.
     */
    public function handle(
        string $checkoutSessionId,
        ?string $paymentIntentId,
        int $amountTotalCents,
        string $paymentStatus,
    ): ?Payment {
        if ($paymentStatus !== 'paid' || ! $paymentIntentId) {
            return null;
        }

        $invoice = Invoice::where('stripe_checkout_session_id', $checkoutSessionId)->first();

        if (! $invoice) {
            return null;
        }

        return DB::transaction(function () use ($invoice, $paymentIntentId, $amountTotalCents): Payment {
            $payment = Payment::firstOrCreate(
                ['stripe_payment_intent_id' => $paymentIntentId],
                [
                    'invoice_id' => $invoice->id,
                    'amount' => $amountTotalCents / 100,
                    'status' => PaymentStatus::Succeeded,
                ],
            );

            if ($invoice->status !== InvoiceStatus::Paid) {
                $invoice->update(['status' => InvoiceStatus::Paid]);
            }

            return $payment;
        });
    }
}
