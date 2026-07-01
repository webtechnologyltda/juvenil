<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum WaitlistEntryStatus: string implements HasColor, HasIcon, HasLabel
{
    case Aguardando = 'aguardando';
    case Convocado = 'convocado';
    case Inscrito = 'inscrito';
    case Desistiu = 'desistiu';
    case Cancelado = 'cancelado';
    case Expirado = 'expirado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Aguardando => 'Aguardando',
            self::Convocado => 'Convocado',
            self::Inscrito => 'Inscrito',
            self::Desistiu => 'Desistiu',
            self::Cancelado => 'Cancelado',
            self::Expirado => 'Expirado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Aguardando => 'warning',
            self::Convocado => 'info',
            self::Inscrito => 'success',
            self::Desistiu, self::Cancelado => 'danger',
            self::Expirado => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Aguardando => 'heroicon-o-clock',
            self::Convocado => 'heroicon-o-link',
            self::Inscrito => 'heroicon-o-check-circle',
            self::Desistiu => 'heroicon-o-arrow-left-on-rectangle',
            self::Cancelado => 'heroicon-o-x-circle',
            self::Expirado => 'heroicon-o-calendar',
        };
    }

    public function isActiveQueueStatus(): bool
    {
        return in_array($this, [self::Aguardando, self::Convocado], true);
    }
}
