<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentPayments extends TableWidget
{
    protected static ?string $heading = 'Recent Payments';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Payment::query()->latest()->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('invoice.invoice_number')
                    ->label('Invoice'),
                TextColumn::make('invoice.client.name')
                    ->label('Client'),
                TextColumn::make('amount')
                    ->money('usd'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime(),
            ]);
    }
}
