<?php

namespace App\Filament\Resources\LancamentoResource\Pages\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

trait NotifiesLancamentoValidation
{
    protected function notifyLancamentoValidationException(ValidationException $exception): void
    {
        Notification::make()
            ->title('Não foi possível salvar o lançamento')
            ->body($this->firstValidationMessage($exception))
            ->danger()
            ->send();
    }

    private function firstValidationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            foreach ($messages as $message) {
                return (string) $message;
            }
        }

        return 'Revise os itens do lançamento e tente novamente.';
    }
}
