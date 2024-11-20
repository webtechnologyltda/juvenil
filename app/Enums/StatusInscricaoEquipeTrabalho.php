<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatusInscricaoEquipeTrabalho : int implements HasColor, HasIcon, HasLabel
{
    case Pendente = 0;

    case Aprovado = 1;

    case Cancelado = 2;


    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pendente => 'warning',
            self::Aprovado => 'success',
            self::Cancelado => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pendente => 'fas-clock',
            self::Aprovado => 'fas-check',
            self::Cancelado => 'fas-times',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Aprovado => 'Aprovado',
            self::Cancelado => 'Cancelado',
        };
    }
}
