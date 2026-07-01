<?php

use App\Actions\Stripe\RecordStripeInvoicePayment;
use App\Actions\Stripe\StripeCheckoutGateway;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::client')] #[Title('Payment successful')] class extends Component {
    public Invoice $invoice;

    public function mount(Invoice $invoice, StripeCheckoutGateway $gateway, RecordStripeInvoicePayment $recordPayment): void
    {
        abort_unless($invoice->client_id === Auth::guard('client')->id(), 403);

        $sessionId = request()->query('session_id');

        if (is_string($sessionId) && $invoice->status !== InvoiceStatus::Paid) {
            $session = $gateway->retrieveSession($sessionId);

            $recordPayment->handle(
                $session->id,
                $session->payment_intent,
                $session->amount_total,
                $session->payment_status,
            );
        }

        $this->invoice = $invoice->fresh();
    }
}; ?>

<div class="flex flex-col items-center gap-6 py-12 text-center">
    @if ($invoice->status === InvoiceStatus::Paid)
        <flux:heading size="xl">{{ __('Payment successful') }}</flux:heading>
        <flux:text>{{ __('Thank you - invoice :number has been paid.', ['number' => $invoice->invoice_number]) }}</flux:text>
    @else
        <flux:heading size="xl">{{ __('Confirming your payment') }}</flux:heading>
        <flux:text>{{ __("We're still confirming your payment with Stripe. This can take a moment - refresh this page shortly.") }}</flux:text>
    @endif

    <flux:link :href="route('client.invoices.show', $invoice)" wire:navigate>
        {{ __('View invoice') }}
    </flux:link>
</div>
