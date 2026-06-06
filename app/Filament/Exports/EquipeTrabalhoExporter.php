<?php

namespace App\Filament\Exports;

use App\Models\EquipeTrabalho;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class EquipeTrabalhoExporter extends Exporter
{
    protected static ?string $model = EquipeTrabalho::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('Cod. Inscricão'),

            ExportColumn::make('nome')
                ->label('Campista'),

            ExportColumn::make('data_form.data_nacimento')
                ->formatStateUsing(fn ($state) => Carbon::createFromFormat('d/m/Y', $state)->age)
                ->label('Idade'),

            ExportColumn::make('data_form.telefone')
                ->label('Telefone'),

            ExportColumn::make('data_form.reponsavel_nome')
                ->label('Responsável'),

            ExportColumn::make('data_form.reponsavel_telefone')
                ->label('Responsável'),

            ExportColumn::make('data_form.tamanho_camiseta')
                ->label('Tamanho da Camiseta')
                ->state(function (EquipeTrabalho $record) {
                    if ($record->data_form['tamanho_camiseta'] === 'O') {
                        return $record->data_form['tamanho_camiseta_outro'];
                    }

                    return $record->data_form['tamanho_camiseta'];
                }),

            ExportColumn::make('data_form.servir_no_acampamento')
                ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não')
                ->label('Pode servir dentro do acampamento ?'),

            ExportColumn::make('data_form.rua')
                ->label('Rua'),

            ExportColumn::make('data_form.numero')
                ->label('Numero'),

            ExportColumn::make('data_form.ponto_referencia')
                ->label('Ponto Referencia'),

            ExportColumn::make('data_form.bairro')
                ->label('Bairro'),

            ExportColumn::make('data_form.cidade')
                ->label('Cidade'),

            ExportColumn::make('data_form.ja_participou_retiro')
                ->label('Ja Participou de Retiro')
                ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não'),

            ExportColumn::make('data_form.pode_missas_diarias')
                ->label('Pode participar de Missas Diarias')
                ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não'),

            ExportColumn::make('data_form.retiro_que_participou')
                ->label('Retiro Que Participou'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return $export->successful_rows.' inscrições da equipe de trabalho exportadas.';
    }
}
