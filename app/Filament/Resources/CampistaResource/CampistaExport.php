<?php

namespace App\Filament\Resources\CampistaResource;

use App\Models\Campista;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;

abstract class CampistaExport
{
    public static function getExportColumns(): array
    {
        return [
            ExportColumn::make('nome')
                ->label('Campista'),

            ExportColumn::make('tribo.cor')
                ->label('Cor da Tribo'),

            ExportColumn::make('form_data.data_nacimento')
                ->formatStateUsing(fn ($state) => Carbon::createFromFormat('d/m/Y', $state)->age)
                ->label('Idade'),

            ExportColumn::make('form_data.peso')
                ->label('Peso'),

            ExportColumn::make('form_data.altura')
                ->label('Altura'),

            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state) => match ($state->value) {
                    0 => 'Pendente',
                    1 => 'Pago',
                    2 => 'Cancelado',
                    default => 'Não Informado',
                }),

            ExportColumn::make('dia_pagamento')
                ->label('Data de Pagamento'),

            ExportColumn::make('forma_pagamento')
                ->label('Forma de Pagamento')
                ->formatStateUsing(fn ($state) => match ($state->value) {
                    1 => 'Pix',
                    2 => 'Dinheiro',
                    3 => 'Cartão',
                    4 => 'Inscrição Gratuíta',
                    default => 'Não Informado',
                }),

            ExportColumn::make('form_data.tamanho_camiseta')
                ->label('Tamanho da Camiseta')
                ->state(function (Campista $record) {
                    if ($record->form_data['tamanho_camiseta'] === 'O') {
                        return $record->form_data['tamanho_camiseta_outro'];
                    }

                    return $record->form_data['tamanho_camiseta'];
                }),

            ExportColumn::make('form_data.rede_social')
                ->label('Rede Social'),

            ExportColumn::make('form_data.remedio')
                ->label('Remedio'),

            ExportColumn::make('form_data.recomendacao')
                ->label('Recomendacao'),

            ExportColumn::make('form_data.nome_pai')
                ->label('Nome do Pai'),

            ExportColumn::make('form_data.nome_mae')
                ->label('Nome da Mae'),

            ExportColumn::make('form_data.telefone_campista')
                ->label('Telefone Campista'),

            ExportColumn::make('form_data.telefone_reponsavel')
                ->label('Telefone Responsável'),

            ExportColumn::make('form_data.rua')
                ->label('Rua'),

            ExportColumn::make('form_data.numero')
                ->label('Numero'),

            ExportColumn::make('form_data.ponto_referencia')
                ->label('Ponto Referencia'),

            ExportColumn::make('form_data.bairro')
                ->label('Bairro'),

            ExportColumn::make('form_data.cidade')
                ->label('Cidade'),

            ExportColumn::make('form_data.ja_participou_retiro')
                ->label('Ja Participou de Retiro')
                ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não'),

            ExportColumn::make('form_data.declaro')
                ->label('Delcaro ter participado do Trekking')
                ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não'),

            ExportColumn::make('form_data.retiro_que_participou')
                ->label('Retiro Que Participou'),

            ExportColumn::make('form_data.algum_parente')
                ->label('Algum Parente Participante')
                ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não'),

            ExportColumn::make('form_data.algum_parente_participante')
                ->label('Algum Parente Participante'),
        ];
    }
}
