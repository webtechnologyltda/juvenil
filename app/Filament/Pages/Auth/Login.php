<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    protected Width | string | null $maxContentWidth = Width::Full;

    protected array $extraBodyAttributes = [
        'class' => 'juvenil-admin-auth-body',
    ];

    public function getTitle(): string | Htmlable
    {
        return 'Login do Painel';
    }

    public function getHeading(): string | Htmlable | null
    {
        return null;
    }

    public function hasLogo(): bool
    {
        return false;
    }
}
