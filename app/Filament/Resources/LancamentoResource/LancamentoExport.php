<?php

namespace App\Filament\Resources\LancamentoResource;

use Filament\Actions\Exports\ExportColumn;

abstract class LancamentoExport
{
    public static function getExportColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('Código'),

            ExportColumn::make('nome')
                ->label('Nome do Lançamento'),

            ExportColumn::make('valor')
                ->label('Valor'),

            ExportColumn::make('comprador')
                ->label('Comprador'),

            ExportColumn::make('data')
                ->label('Data de Pagamento'),

            ExportColumn::make('tipo')
                ->label('Tipo de Lançamento')
                ->formatStateUsing(fn ($state) => match ($state->value) {
                    0 => 'Receita',
                    1 => 'Despesa',
                    2 => 'Doação',
                    default => 'Indefinido',
                }),

            ExportColumn::make('categories_summary')
                ->label('Categorias'),

            ExportColumn::make('batch_code')
                ->label('Lote'),

            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => match ($state->value) {
                    0 => 'Pendente',
                    1 => 'Pago',
                    2 => 'Cancelado',
                    default => 'Indefinido',
                }),

            ExportColumn::make('forma_pagamento')
                ->label('Forma de Pagamento')
                ->formatStateUsing(fn ($state) => match ($state->value) {
                    1 => 'Pix',
                    2 => 'Dinheiro',
                    3 => 'Cartão',
                    4 => 'Não Pago',
                    default => 'Indefinido',
                }),

            ExportColumn::make('descricao')
                ->label('Descrição'),
        ];
    }
}
