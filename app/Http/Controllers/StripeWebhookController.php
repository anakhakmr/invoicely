<?php

namespace App\Http\Controllers;

use App\Actions\Stripe\RecordStripeInvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, RecordStripeInvoicePayment $recordPayment): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('services.stripe.webhook_secret'),
            );
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response('Invalid payload or signature.', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $recordPayment->handle(
                $session['id'],
                $session['payment_intent'],
                $session['amount_total'],
                $session['payment_status'],
            );
        }

        return response('OK', 200);
    }
}
