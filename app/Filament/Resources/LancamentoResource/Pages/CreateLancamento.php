<?php

namespace App\Filament\Resources\LancamentoResource\Pages;

use App\Enums\TipoLacamento;
use App\Filament\Resources\LancamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLancamento extends CreateRecord
{
    protected static string $resource = LancamentoResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['tipo'] == TipoLacamento::Despesa->value && $data['valor'] > 0) || $data['tipo'] != TipoLacamento::Despesa->value && $data['valor'] < 0) {
            $data['valor'] *= -1;
        }

        return parent::mutateFormDataBeforeCreate($data);
    }
}
