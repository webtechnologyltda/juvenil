<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function request(): void
    {
        Notification::make()
            ->title('Solicite o link a um administrador')
            ->body('Nenhum e-mail de redefinição será enviado por este sistema.')
            ->info()
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make('Este sistema não envia links de redefinição por e-mail. Solicite seu link a um administrador e abra-o para cadastrar uma nova senha.'),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Recuperação de senha';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Solicite seu link ao administrador';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [$this->loginAction()];
    }
}
