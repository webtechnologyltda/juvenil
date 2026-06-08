<?php

namespace App\Filament\Resources\CategoriaLancamentoResource\Pages;

use App\Filament\Resources\CategoriaLancamentoResource;
use Filament\Actions\DeleteAction;
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
}
