<?php

namespace App\Filament\Resources\EquipeTrabalhoResource;

use Carbon\Carbon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;

abstract class EquipeTrabalhoTable
{
    public static function getColumns(): array
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

            TextColumn::make('data_form.data_nacimento')
                ->label('Idade')
                ->numeric()
                ->sortable()
                ->alignCenter()
                ->formatStateUsing(fn($state) => Carbon::createFromFormat('d/m/Y', $state)->age)
                ->toggleable(isToggledHiddenByDefault: true),

            SelectColumn::make('status')
                ->alignCenter()
                ->label('Status'),

            TextColumn::make('data_form.tamanho_camiseta')
                ->label('Tamanho da Camiseta')
                ->alignCenter()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('data_form.reponsavel_nome')
                ->label('Nome do Responsável')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('data_form.reponsavel_telefone')
                ->label('Telefone Responsável')
                ->alignCenter()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }
}
