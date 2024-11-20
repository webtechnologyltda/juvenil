<?php

namespace App\Enums;

use Filament\Support\Contracts\{HasColor, HasIcon, HasLabel};

enum StatusInscricao: int implements HasColor, HasLabel, HasIcon
{
    case Pendente = 0;
    case Pago = 1;
    case Cancelado = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Pago => 'Pago',
            self::Cancelado => 'Cancelado'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pendente => 'warning',
            self::Pago => 'success',
            self::Cancelado => 'danger'
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pendente => 'fas-clock',
            self::Pago => 'polaris-payment-icon',
            self::Cancelado => 'fas-xmark'
        };
    }
}
