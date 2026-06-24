<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TipoEquipeTrabalho: int implements HasColor, HasIcon, HasLabel
{
    case Interna = 0;
    case Externa = 1;

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Interna => 'success',
            self::Externa => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Interna => 'heroicon-o-home-modern',
            self::Externa => 'heroicon-o-map-pin',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Interna => 'Interna',
            self::Externa => 'Externa',
        };
    }

    public function configuredAmountField(): string
    {
        return match ($this) {
            self::Interna => 'valor_equipe_trabalho_interna',
            self::Externa => 'valor_equipe_trabalho_externa',
        };
    }
}
