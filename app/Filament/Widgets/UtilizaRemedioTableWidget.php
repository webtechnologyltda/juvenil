<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UtilizaRemedioTableWidget extends BaseWidget
{

    use HasWidgetShield;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 10;

    public function table(Table $table): Table
    {

        return $table
            ->heading('Informações Médicas')
            ->query(
                Campista::query()
                    ->where('status', '<>', StatusInscricao::Cancelado->value)
                    ->whereJsonContains('form_data', ['toma_remedio' => "1"])
                    ->orWhereJsonContains('form_data', ['tem_recomendacao' => "1"])
            )
            ->groups([
                Group::make('tribo.cor')
                    ->label('Tribo'),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn(Campista $record) => route('filament.admin.resources.campistas.view', $record->id))
                    ->label('Ver Inscricão')
            ])
            ->columns([
                TextColumn::make('nome')
                    ->sortable()
                    ->searchable()
                    ->label('Nome'),

                TextColumn::make('tribo.cor')
                    ->sortable()
                    ->searchable()
                    ->alignCenter()
                    ->label('Tribo'),

                TextColumn::make('form_data.data_nacimento')
                    ->label('Idade')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => Carbon::createFromFormat('d/m/Y', $state)->age),

                TextColumn::make('form_data.altura')
                    ->label('Altura')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => $state . ' cm')
                    ->toggleable(),

                TextColumn::make('form_data.peso')
                    ->label('Peso')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => $state . ' Kg')
                    ->toggleable(),

                IconColumn::make('form_data.toma_remedio')
                    ->label('Toma remédio')
                    ->alignCenter()
                    ->icon(function (Campista $record) {
                        return $record->form_data['toma_remedio'] ? 'healthicons-f-medicines' : 'heroicon-o-x-circle';
                    })
                    ->tooltip(function (Campista $record) {
                        return $record->form_data['toma_remedio'] ? 'Sim' : 'Não';
                    })
                    ->color(function (Campista $record) {
                        return $record->form_data['toma_remedio'] ? 'info' : 'danger';
                    }),

                IconColumn::make('form_data.tem_recomendacao')
                    ->label('Tem recomendação de cuidados')
                    ->alignCenter()
                    ->icon(function (Campista $record) {
                        return $record->form_data['tem_recomendacao'] ? 'healthicons-f-stethoscope' : 'heroicon-o-x-circle';
                    })
                    ->tooltip(function (Campista $record) {
                        return $record->form_data['tem_recomendacao'] ? 'Sim' : 'Não';
                    })
                    ->color(function (Campista $record) {
                        return $record->form_data['tem_recomendacao'] ? 'info' : 'danger';
                    }),

            ]);
    }
}
