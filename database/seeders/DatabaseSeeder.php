<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Client::factory()
            ->count(5)
            ->create()
            ->each(function (Client $client): void {
                Invoice::factory()
                    ->count(3)
                    ->create(['client_id' => $client->id])
                    ->each(function (Invoice $invoice): void {
                        $items = InvoiceItem::factory()
                            ->count(fake()->numberBetween(1, 4))
                            ->create(['invoice_id' => $invoice->id]);

                        $invoice->update([
                            'total' => $items->sum(fn (InvoiceItem $item): float => $item->quantity * (float) $item->unit_price),
                        ]);

                        if ($invoice->status === InvoiceStatus::Paid) {
                            Payment::factory()->create([
                                'invoice_id' => $invoice->id,
                                'amount' => $invoice->total,
                                'status' => PaymentStatus::Succeeded,
                            ]);
                        }
                    });
            });
    }
}
