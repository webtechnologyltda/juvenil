<?php

namespace App\Filament\Resources\EquipeTrabalhoResource;

use App\Enums\StatusInscricaoEquipeTrabalho;
use Carbon\Carbon;
use Filament\Support\Colors\Color;
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
                ->options(StatusInscricaoEquipeTrabalho::class)
                ->label('Status'),

            TextColumn::make('data_form.sexo')
                ->alignCenter()
                ->badge()
                ->toggleable(isToggledHiddenByDefault: true)
                ->icon(fn ($state) => $state === 'M' ? 'eos-male' : 'eos-female')
                ->formatStateUsing(fn ($state) => $state === 'M' ? 'Masculino' : 'Feminino')
                ->color(fn ($state) => $state === 'M' ? Color::Blue : Color::Pink)
                ->sortable()
                ->label('Sexo'),

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
