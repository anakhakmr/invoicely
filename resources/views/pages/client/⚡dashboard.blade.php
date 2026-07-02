<?php

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::client')] #[Title('My Invoices')] class extends Component {
    use WithPagination;

    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        return Invoice::query()
            ->where('client_id', Auth::guard('client')->id())
            ->latest('due_date')
            ->paginate(10);
    }
}; ?>

<div class="flex flex-col gap-6">
    <flux:heading size="xl">{{ __('My Invoices') }}</flux:heading>

    @if ($this->invoices->isEmpty())
        <flux:text>{{ __('You have no invoices yet.') }}</flux:text>
    @else
        <flux:table :paginate="$this->invoices">
            <flux:table.columns>
                <flux:table.column>{{ __('Invoice') }}</flux:table.column>
                <flux:table.column>{{ __('Due date') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->invoices as $invoice)
                    <flux:table.row :key="$invoice->id">
                        <flux:table.cell>{{ $invoice->invoice_number }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->due_date->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell class="py-0">
                            <flux:badge size="sm" :color="$invoice->status->getFluxColor()">
                                {{ $invoice->status->getLabel() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell variant="strong">${{ number_format((float) $invoice->total, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:link :href="route('client.invoices.show', $invoice)" wire:navigate>
                                {{ __('View') }}
                            </flux:link>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>
