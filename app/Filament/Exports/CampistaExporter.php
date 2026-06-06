<?php

namespace App\Filament\Exports;

use App\Filament\Resources\CampistaResource\CampistaExport;
use App\Models\Campista;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CampistaExporter extends Exporter
{
    protected static ?string $model = Campista::class;

    public static function getColumns(): array
    {
        return CampistaExport::getExportColumns();
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return $export->successful_rows.' inscrições exportadas.';
    }
}
