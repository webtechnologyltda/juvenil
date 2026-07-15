<?php

namespace App\Filament\Resources\UserResource\Actions;

use App\Models\User;
use App\Support\Auth\ManualPasswordResetLink;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;

class GeneratePasswordResetLinkAction
{
    public static function make(): Action
    {
        return Action::make('generatePasswordResetLink')
            ->label('Gerar link de redefinição')
            ->icon('heroicon-o-key')
            ->color('warning')
            ->authorize('update')
            ->disabled(fn (User $record): bool => ! $record->canAccessPanel(Filament::getCurrentOrDefaultPanel()))
            ->tooltip(fn (User $record): ?string => $record->canAccessPanel(Filament::getCurrentOrDefaultPanel())
                ? null
                : 'Atribua um perfil de acesso ao usuário antes de gerar o link.')
            ->modalHeading(fn (User $record): string => 'Redefinir a senha de '.$record->name)
            ->modalDescription(fn (): string => sprintf(
                'Copie e envie este link ao usuário. Ele é válido por %d minutos e um novo link invalidará o anterior.',
                app(ManualPasswordResetLink::class)->expirationMinutes(),
            ))
            ->modalWidth(Width::Large)
            ->schema([
                TextInput::make('reset_url')
                    ->label('Link de redefinição de senha')
                    ->readOnly()
                    ->copyable(
                        copyMessage: 'Link copiado',
                        copyMessageDuration: 2000,
                    ),
            ])
            ->fillForm(fn (User $record): array => [
                'reset_url' => app(ManualPasswordResetLink::class)->generate($record),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->closeModalByClickingAway(false);
    }
}
