<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LiberacaoInscricoesStatusEnum : int implements HasColor, HasLabel, HasIcon
{
    case LIBERADO = 0;
    case TRANCADO = 1;
    case ENCERRADO = 2;

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::LIBERADO => 'success',
            self::TRANCADO => 'warning',
            self::ENCERRADO => 'info',
            default => 'gray'
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::LIBERADO => 'fas-lock-open',
            self::TRANCADO => 'fas-lock',
            self::ENCERRADO => 'fas-flag-checkered',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LIBERADO => 'Liberado',
            self::TRANCADO => 'Trancado',
            self::ENCERRADO => 'Encerrado',
        };
    }
}
