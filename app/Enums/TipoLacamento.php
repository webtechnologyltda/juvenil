<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TipoLacamento: int implements HasColor, HasIcon, HasLabel
{
    case Receita = 0;
    case Despesa = 1;
    case Doacao = 2;

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Despesa => 'danger',
            self::Receita => 'success',
            self::Doacao => 'info'
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Receita => 'heroicon-m-arrow-trending-up',
            self::Despesa => 'heroicon-m-arrow-trending-down',
            self::Doacao => 'iconoir-donate'
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Receita => 'Receita',
            self::Despesa => 'Despesa',
            self::Doacao => 'Doação'
        };
    }
}
