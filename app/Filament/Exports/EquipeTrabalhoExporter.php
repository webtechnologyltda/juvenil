<?php

namespace App\Filament\Exports;

use App\Models\EquipeTrabalho;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Throwable;

class EquipeTrabalhoExporter extends Exporter
{
    protected static ?string $model = EquipeTrabalho::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('Cod. Inscricão'),

            ExportColumn::make('nome')
                ->label('Nome'),

            ExportColumn::make('descricao')
                ->label('Equipe'),

            ExportColumn::make('data_form.data_nacimento')
                ->formatStateUsing(fn (mixed $state): ?int => self::ageFromBirthDate($state))
                ->label('Idade'),

            ExportColumn::make('data_form.telefone')
                ->label('Telefone'),

            ExportColumn::make('data_form.reponsavel_nome')
                ->label('Responsável'),

            ExportColumn::make('data_form.reponsavel_telefone')
                ->label('Responsável'),

            ExportColumn::make('data_form.tamanho_camiseta')
                ->label('Tamanho da Camiseta')
                ->state(function (EquipeTrabalho $record): ?string {
                    $shirtSize = data_get($record->data_form, 'tamanho_camiseta');

                    if ($shirtSize === 'O') {
                        return data_get($record->data_form, 'tamanho_camiseta_outro');
                    }

                    return $shirtSize;
                }),

            ExportColumn::make('data_form.servir_no_acampamento')
                ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não')
                ->label('Pode servir dentro do acampamento ?'),

            ExportColumn::make('data_form.rua')
                ->label('Rua'),

            ExportColumn::make('data_form.numero')
                ->label('Numero'),

            ExportColumn::make('data_form.ponto_referencia')
                ->label('Complemento'),

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

    private static function ageFromBirthDate(mixed $state): ?int
    {
        if (! is_string($state) || trim($state) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $state)->age;
        } catch (Throwable) {
            return null;
        }
    }
}
