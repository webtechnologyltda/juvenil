<?php

namespace App\Filament\Resources\CategoriaLancamentoResource\Pages;

use App\Filament\Resources\CategoriaLancamentoResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCategoriaLancamento extends EditRecord
{
    protected static string $resource = CategoriaLancamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => ! $this->record->isSystemDefault()),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Categoria de lançamento salva')
            ->body('As alterações foram aplicadas.');
    }
}
