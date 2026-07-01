<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('invoice_number')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'INV-'.strtoupper(Str::random(6))),
                Select::make('status')
                    ->options(InvoiceStatus::class)
                    ->default(InvoiceStatus::Draft)
                    ->required(),
                DatePicker::make('due_date')
                    ->required(),
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        TextInput::make('description')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('quantity')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->live(onBlur: true)
                            ->required(),
                        TextInput::make('unit_price')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->live(onBlur: true)
                            ->required(),
                    ])
                    ->columns(4)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotal($get, $set))
                    ->addActionLabel('Add item')
                    ->minItems(1)
                    ->required(),
                TextInput::make('total')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    protected static function updateTotal(Get $get, Set $set): void
    {
        $total = collect((array) $get('items'))->sum(
            fn (?array $item): float => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        );

        $set('total', number_format($total, 2, '.', ''));
    }
}
