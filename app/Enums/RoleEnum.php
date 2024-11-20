<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RoleEnum: int implements HasColor, HasLabel
{
    case SuperAdministrador = 1;
    case UsuarioComum = 2;
    case Financeiro = 3;




    public static function getRoleEnum($role)
    {
        return match ($role) {
            1 => RoleEnum::SuperAdministrador,
            3 => RoleEnum::Financeiro,
            default => RoleEnum::UsuarioComum
        };
    }

    public static function getRoleEnumDescriptionById($role)
    {
        return match ($role) {
            1 => 'Super Administrador',
            3 => 'Financeiro',
            default => 'Usuário Comum'
        };
    }

    public static function getRoleEnumDescription($role)
    {
        return match ($role) {
            self::SuperAdministrador => 'Super Administrador',
            self::Financeiro => 'Financeiro',
            default => 'Usuário Comum'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SuperAdministrador => 'danger',
            self::Financeiro => 'warning',
            default => 'info'
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SuperAdministrador => 'Super Administrador',
            self::Financeiro => 'Financeiro',
            default => 'Usuário Comum'
        };
    }
}
