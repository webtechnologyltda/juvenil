<?php

namespace App\Filament\Resources\CampistaResource;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Models\Campista;
use App\Models\Tribo;
use Carbon\Carbon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

abstract class CampistaTable
{

    public static function getListTableColumns(): array
    {
        return [
            TextColumn::make('id')
                ->label('Cód.')
                ->sortable()
                ->searchable(),

            ImageColumn::make('avatar_url')
                ->square()
                ->alignCenter()
                ->size(60)
                ->grow(false)
                ->extraImgAttributes(['class' => 'rounded-xl'])
                ->label('Foto'),

            TextColumn::make('nome')
                ->sortable()
                ->searchable(),

            SelectColumn::make('tribo_id')
                ->label('Cor da Tribo')
                ->placeholder('Selecione uma tribo')
                ->options(Tribo::all()->pluck('cor', 'id'))
                ->sortable()
                ->visible( fn() => auth()->user()->can('update_campista', Campista::class ))
                ->searchable(true),

            TextColumn::make('tribo.cor')
                ->label('Cor da Tribo')
                ->sortable()
                ->hidden( fn() => auth()->user()->can('update_campista', Campista::class))
                ->searchable(true),

            TextColumn::make('form_data.data_nacimento')
                ->label('Idade')
                ->numeric()
                ->sortable()
                ->alignCenter()
                ->formatStateUsing(fn($state) => Carbon::createFromFormat('d/m/Y', $state)->age)
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('status')
                ->badge()
                ->alignCenter()
                ->label('Status'),

            TextColumn::make('forma_pagamento')
                ->badge()
                ->alignCenter()
                ->label('Meio de Pagamento'),

            IconColumn::make('presenca')
                ->label('Presença')
                ->alignCenter()
                ->icon(fn($state) => $state ? 'heroicon-o-hand-thumb-up' : 'heroicon-o-hand-thumb-down')
                ->tooltip(fn($state) => $state ? 'Presença Confirmada' : 'Não Compareceu')
                ->boolean(),

            TextColumn::make('dia_pagamento')
                ->label('Dia de Pagamento')
                ->formatStateUsing(fn($state) => Carbon::parse($state)->format('d/m/Y'))
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.tamanho_camiseta')
                ->label('Tamanho da Camiseta')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.nome_pai')
                ->label('Nome do Pai')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.nome_mae')
                ->label('Nome da Mae')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.telefone_campista')
                ->label('Telefone Campista')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.telefone_reponsavel')
                ->label('Telefone Responsável')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.rua')
                ->label('Rua')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.numero')
                ->label('Numero')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.ponto_referencia')
                ->label('Ponto Referencia')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.bairro')
                ->label('Bairro')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.cidade')
                ->label('Cidade')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.ja_participou_retiro')
                ->label('Ja Participou de Retiro')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.retiro_que_participou')
                ->label('Retiro Que Participou')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.algum_parente')
                ->label('Algum Parente Participante')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('form_data.algum_parente_participante')
                ->label('Algum Parente Participante')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

}
