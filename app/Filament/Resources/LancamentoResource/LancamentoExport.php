<?php

namespace App\Filament\Resources\LancamentoResource;

use App\Enums\TipoLacamento;
use Filament\Tables\Columns\TextColumn;
use pxlrbt\FilamentExcel\Columns\Column;

abstract class LancamentoExport
{

    public static function getExportColumns(): array
    {
        return [
            Column::make('id')
                ->heading('Código'),

            Column::make('nome')
                ->heading('Nome do Lançamento'),

            Column::make('valor')
                ->heading('Valor'),

            Column::make('comprador')
                ->heading( 'Comprador' ),

            Column::make('data')
                ->heading('Data de Pagamento'),



            Column::make( 'tipo')
                ->heading( 'Tipo de Lançamento' )
                ->formatStateUsing(function ($state) {
                    return match ($state -> value) {
                        0 => 'Receita',
                        1 => 'Despesa',
                        2 => 'Doação',
                        default => 'Indefinido',

                    };
                }),

            Column::make('status')
                ->heading('Status')
                ->formatStateUsing(function ($state) {
                   return match ($state -> value) {
                       0 => 'Pendente',
                       1 => 'Pago',
                       2 => 'Cancelado',
                       default => 'Indefinido',
                   };
                }),

            Column::make('forma_pagamento')
                ->heading('Forma de Pagamento')
                ->formatStateUsing(function ($state) {
                    return match ($state -> value) {
                        1 => 'Pix',
                        2 => 'Dinheiro',
                        3 => 'Cartão',
                        4 => 'Não Pago',
                        default => 'Indefinido',
                    };
                }),

            Column::make('descricao')
                ->heading('Descrição'),
        ];
    }

}
