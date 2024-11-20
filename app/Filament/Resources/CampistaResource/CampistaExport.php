<?php

namespace App\Filament\Resources\CampistaResource;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use Carbon\Carbon;
use pxlrbt\FilamentExcel\Columns\Column;

abstract class CampistaExport
{
    public static function getExportColumns(): array
    {
        return [
            Column::make('nome')
                ->heading('Campista'),
            Column::make('tribo')
                ->formatStateUsing(fn($state) => $state->cor)
                ->heading('Cor da Tribo'),

            Column::make('form_data.data_nacimento')
                ->formatStateUsing(fn($state) => Carbon::createFromFormat('d/m/Y', $state)->age)
                ->heading('Idade'),

            Column::make('form_data.peso')
                ->heading('Peso'),

            Column::make('form_data.altura')
                ->heading('Altura'),

            Column::make('status')
                ->heading('Status')
                ->formatStateUsing(function ($state) {
                    switch ($state -> value) {
                        case 0:
                            return 'Pendente';

                        case 1:
                            return 'Pago';

                        case 2:
                            return 'Cancelado';
                    }
                }),

            Column::make('dia_pagamento')
                ->heading('Data de Pagamento'),

            Column::make('forma_pagamento')
                ->heading('Forma de Pagamento')
                ->formatStateUsing(function ($state) {
                    switch ($state -> value) {
                        case 1:
                            return 'Pix';

                        case 2:
                            return 'Dinheiro';

                        case 3:
                            return 'Cartão';
                        case 4:
                            return 'Inscrição Gratuíta';
                            default:
                                return 'Não Informado';
                    }
                }),

            Column::make('form_data.tamanho_camiseta')
                ->heading('Tamanho da Camiseta')
                ->formatStateUsing(function ($record) {
                    if ($record->form_data['tamanho_camiseta'] == 'O') {
                        return $record->form_data['tamanho_camiseta_outro'];
                    } else {
                        return $record->form_data['tamanho_camiseta'];
                    }
                }),

            Column::make('form_data.rede_social')
                ->heading('Rede Social'),

            Column::make('form_data.remedio')
                ->heading('Remedio'),

            Column::make('form_data.recomendacao')
                ->heading('Recomendacao'),

            Column::make('form_data.nome_pai')
                ->heading('Nome do Pai'),

            Column::make('form_data.nome_mae')
                ->heading('Nome da Mae'),

            Column::make('form_data.telefone_campista')
                ->heading('Telefone Campista'),

            Column::make('form_data.telefone_reponsavel')
                ->heading('Telefone Responsável'),

            Column::make('form_data.rua')
                ->heading('Rua'),

            Column::make('form_data.numero')
                ->heading('Numero'),

            Column::make('form_data.ponto_referencia')
                ->heading('Ponto Referencia'),

            Column::make('form_data.bairro')
                ->heading('Bairro'),

            Column::make('form_data.cidade')
                ->heading('Cidade'),

            Column::make('form_data.ja_participou_retiro')
                ->heading('Ja Participou de Retiro')
                ->formatStateUsing(fn($state) => $state ? 'Sim' : 'Não'),

            Column::make('form_data.declaro')
                ->heading('Delcaro ter participado do Trekking')
                ->formatStateUsing(fn($state) => $state ? 'Sim' : 'Não'),

            Column::make('form_data.retiro_que_participou')
                ->heading('Retiro Que Participou'),

            Column::make('form_data.algum_parente')
                ->heading('Algum Parente Participante')
                ->formatStateUsing(fn($state) => $state ? 'Sim' : 'Não'),

            Column::make('form_data.algum_parente_participante')
                ->heading('Algum Parente Participante'),
        ];
    }

}
