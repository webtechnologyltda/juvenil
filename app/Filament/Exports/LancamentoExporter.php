<?php

namespace App\Filament\Exports;

use App\Filament\Resources\LancamentoResource\LancamentoExport;
use App\Models\Lancamento;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LancamentoExporter extends Exporter
{
    protected static ?string $model = Lancamento::class;

    public static function getColumns(): array
    {
        return LancamentoExport::getExportColumns();
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return $export->successful_rows.' lançamentos exportados.';
    }
}
