<?php

namespace App\Filament\Resources\TriboResource\Pages;

use App\Filament\Resources\TriboResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTribo extends EditRecord
{
    protected static string $resource = TriboResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
