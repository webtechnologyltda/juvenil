<?php

namespace App\Filament\Resources\LancamentoResource\Pages;

use App\Enums\TipoLacamento;
use App\Filament\Resources\LancamentoResource;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLancamento extends EditRecord
{
    protected static string $resource = LancamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['comprovante'] = LancamentoForm::normalizeComprovanteState($data['comprovante'] ?? null);

        return parent::mutateFormDataBeforeFill($data);
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['tipo'] == TipoLacamento::Despesa->value && $data['valor'] > 0) || $data['tipo'] != TipoLacamento::Despesa->value && $data['valor'] < 0) {
            $data['valor'] *= -1;
        }
        return parent::mutateFormDataBeforeSave($data);
    }
}
