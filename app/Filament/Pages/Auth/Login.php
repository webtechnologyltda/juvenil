<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Support\Enums\Width;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected array $extraBodyAttributes = [
        'class' => 'juvenil-admin-auth-body',
    ];

    public function getTitle(): string|Htmlable
    {
        return 'Login do Painel';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            return parent::authenticate();
        } catch (ValidationException $exception) {
            $isAuthenticationFailure = in_array(
                __('filament-panels::auth/pages/login.messages.failed'),
                $exception->errors()['data.email'] ?? [],
                true,
            );

            if ((! $isAuthenticationFailure) || (! $this->hasValidCredentialsWithoutPanelAccess())) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'data.email' => 'A senha está correta, mas este usuário não possui um perfil de acesso ao painel. Solicite a um administrador que atribua um perfil.',
            ]);
        }
    }

    protected function hasValidCredentialsWithoutPanelAccess(): bool
    {
        $data = $this->data ?? [];

        if (blank($data['email'] ?? null) || blank($data['password'] ?? null)) {
            return false;
        }

        /** @var SessionGuard $authGuard */
        $authGuard = Filament::auth();
        $authProvider = $authGuard->getProvider();
        $credentials = $this->getCredentialsFromFormData($data);
        $user = $authProvider->retrieveByCredentials($credentials);

        if ((! $user) || (! $authProvider->validateCredentials($user, $credentials))) {
            return false;
        }

        return ($user instanceof FilamentUser)
            && (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()));
    }
}
