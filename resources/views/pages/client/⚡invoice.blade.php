<?php

use App\Actions\Stripe\CreateStripeCheckoutSession;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::client')] #[Title('Invoice')] class extends Component {
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        abort_unless($invoice->client_id === Auth::guard('client')->id(), 403);

        $this->invoice = $invoice->load('items');
    }

    public function pay(CreateStripeCheckoutSession $action): void
    {
        try {
            $session = $action->handle($this->invoice);
        } catch (\RuntimeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->redirect($session->url);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $invoice->invoice_number }}</flux:heading>
            <flux:text class="mt-1">{{ __('Due :date', ['date' => $invoice->due_date->format('F j, Y')]) }}</flux:text>
        </div>

        <flux:badge :color="$invoice->status->getFluxColor()">
            {{ $invoice->status->getLabel() }}
        </flux:badge>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Description') }}</flux:table.column>
            <flux:table.column>{{ __('Quantity') }}</flux:table.column>
            <flux:table.column>{{ __('Unit price') }}</flux:table.column>
            <flux:table.column>{{ __('Amount') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($invoice->items as $item)
                <flux:table.row :key="$item->id">
                    <flux:table.cell>{{ $item->description }}</flux:table.cell>
                    <flux:table.cell>{{ $item->quantity }}</flux:table.cell>
                    <flux:table.cell>${{ number_format((float) $item->unit_price, 2) }}</flux:table.cell>
                    <flux:table.cell>${{ number_format($item->quantity * (float) $item->unit_price, 2) }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <div class="flex justify-end">
        <div class="w-full max-w-xs space-y-4">
            <div class="flex justify-between">
                <flux:heading>{{ __('Total') }}</flux:heading>
                <flux:heading>${{ number_format((float) $invoice->total, 2) }}</flux:heading>
            </div>

            @if ($invoice->status !== InvoiceStatus::Paid)
                <flux:button variant="primary" wire:click="pay" class="w-full" data-test="pay-now-button">
                    {{ __('Pay Now') }}
                </flux:button>
            @endif
        </div>
    </div>

    <div>
        <flux:link :href="route('client.dashboard')" wire:navigate>
            &larr; {{ __('Back to invoices') }}
        </flux:link>
    </div>
</div>
