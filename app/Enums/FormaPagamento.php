<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum FormaPagamento: int implements HasLabel, HasColor, HasIcon
{
    case Pix = 1;
    case Dinheiro = 2;

    case Cartao = 3;
    case NaoPago = 4;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pix => 'Pix',
            self::Dinheiro => 'Dinheiro',
            self::Cartao => 'Cartão',
            self::NaoPago => 'Não Pago',
            default => 'Não Informado'
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Pix => 'teal',
            self::Dinheiro => 'success',
            self::Cartao => 'orange',
            self::NaoPago => 'violet',
            default => 'gray'
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pix => 'fab-pix',
            self::Dinheiro => 'fas-money-bill-wave',
            self::Cartao => 'fas-credit-card',
            self::NaoPago => 'ri-discount-percent-fill'
        };
    }
}
