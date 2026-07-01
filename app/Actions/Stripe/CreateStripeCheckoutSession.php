<?php

namespace App\Actions\Stripe;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use RuntimeException;
use Stripe\Checkout\Session;

class CreateStripeCheckoutSession
{
    public function __construct(private StripeCheckoutGateway $gateway) {}

    public function handle(Invoice $invoice): Session
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw new RuntimeException('This invoice has already been paid.');
        }

        $invoice->loadMissing(['items', 'client']);

        $session = $this->gateway->createSession([
            'mode' => 'payment',
            'customer_email' => $invoice->client->email,
            'line_items' => $invoice->items->map(fn (InvoiceItem $item): array => [
                'quantity' => $item->quantity,
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => (int) round(((float) $item->unit_price) * 100),
                    'product_data' => [
                        'name' => $item->description,
                    ],
                ],
            ])->all(),
            'success_url' => route('client.invoices.checkout.success', $invoice).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('client.invoices.checkout.cancel', $invoice),
            'metadata' => [
                'invoice_id' => (string) $invoice->id,
            ],
        ]);

        $invoice->update(['stripe_checkout_session_id' => $session->id]);

        return $session;
    }
}
