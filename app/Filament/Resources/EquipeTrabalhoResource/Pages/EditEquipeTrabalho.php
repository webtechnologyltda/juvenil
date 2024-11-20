<?php

namespace App\Filament\Resources\EquipeTrabalhoResource\Pages;

use App\Filament\Resources\EquipeTrabalhoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipeTrabalho extends EditRecord
{
    protected static string $resource = EquipeTrabalhoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
