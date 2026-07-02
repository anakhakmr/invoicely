<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalRevenue = (float) Payment::query()
            ->where('status', PaymentStatus::Succeeded)
            ->sum('amount');

        $outstandingInvoices = Invoice::query()->where('status', '!=', InvoiceStatus::Paid);
        $outstandingCount = (clone $outstandingInvoices)->count();
        $outstandingTotal = (float) (clone $outstandingInvoices)->sum('total');

        return [
            Stat::make('Total Revenue', '$'.number_format($totalRevenue, 2))
                ->color('success'),
            Stat::make('Outstanding Invoices', (string) $outstandingCount)
                ->description('$'.number_format($outstandingTotal, 2).' outstanding')
                ->color('warning'),
        ];
    }
}
