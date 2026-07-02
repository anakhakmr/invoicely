<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::Paid => 'success',
            self::Overdue => 'danger',
        };
    }

    /**
     * Color name understood by Flux UI's badge component (not Filament's semantic palette).
     */
    public function getFluxColor(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Sent => 'blue',
            self::Paid => 'green',
            self::Overdue => 'red',
        };
    }
}
