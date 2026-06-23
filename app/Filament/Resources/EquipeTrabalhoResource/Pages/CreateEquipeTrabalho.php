<?php

namespace App\Filament\Resources\EquipeTrabalhoResource\Pages;

use App\Filament\Resources\EquipeTrabalhoResource;
use App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoForm;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateEquipeTrabalho extends CreateRecord
{
    protected static string $resource = EquipeTrabalhoResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                EquipeTrabalhoForm::getAdminFormCreate(),
            );
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['data_form'] ??= [];

        return static::getModel()::query()->create($data);
    }
}
