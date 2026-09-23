<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeliveryStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pendiente';
    case DELIVERED = 'entregado';
    case CANCELLED = 'cancelado';
    case PARTIAL = 'parcial';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::DELIVERED => 'Entregado',
            self::CANCELLED => 'Cancelado',
            self::PARTIAL => 'Parcial',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
            self::PARTIAL => 'info',
        };
    }
}
