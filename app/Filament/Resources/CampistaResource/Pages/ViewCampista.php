<?php

namespace App\Filament\Resources\CampistaResource\Pages;

use App\Filament\Resources\CampistaResource;
use App\Filament\Resources\CampistaResource\CampistaForm;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewCampista extends ViewRecord
{
    protected static string $resource = CampistaResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ...CampistaForm::getFormView(),
            ]);
    }
}
