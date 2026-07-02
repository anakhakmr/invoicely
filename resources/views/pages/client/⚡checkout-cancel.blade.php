<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::client')] #[Title('Checkout cancelled')] class extends Component {
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        abort_unless($invoice->client_id === Auth::guard('client')->id(), 403);

        $this->invoice = $invoice;
    }
}; ?>

<div class="flex flex-col items-center gap-6 py-12 text-center">
    <flux:heading size="xl">{{ __('Checkout cancelled') }}</flux:heading>
    <flux:text>{{ __('No payment was made for invoice :number.', ['number' => $invoice->invoice_number]) }}</flux:text>

    <flux:link :href="route('client.invoices.show', $invoice)" wire:navigate>
        {{ __('Back to invoice') }}
    </flux:link>
</div>
