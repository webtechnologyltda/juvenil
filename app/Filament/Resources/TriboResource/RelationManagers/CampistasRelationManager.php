<?php

namespace App\Filament\Resources\TriboResource\RelationManagers;

use App\Filament\Resources\CampistaResource;
use App\Models\Campista;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CampistasRelationManager extends RelationManager
{
    protected static string $relationship = 'campistas';

    protected static ?string $title = 'Campistas da tribo';

    protected static ?string $label = 'Campista';

    protected static ?string $pluralLabel = 'Campistas';

    protected static string|\BackedEnum|null $icon = 'heroicon-s-user-group';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->campistas()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome')
            ->heading('Campistas da tribo')
            ->description('Inscrições de campistas vinculadas a esta tribo.')
            ->recordUrl(fn (Campista $record): string => CampistaResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->label('Cód.')
                    ->sortable()
                    ->visibleFrom('md'),

                ImageColumn::make('avatar_url')
                    ->state(fn (Campista $record): ?string => filter_var($record->avatar_url, FILTER_VALIDATE_URL) ? null : $record->avatar_url)
                    ->disk('public')
                    ->square()
                    ->alignCenter()
                    ->size(48)
                    ->grow(false)
                    ->defaultImageUrl(asset('img/logo.png'))
                    ->label('Foto'),

                TextColumn::make('nome')
                    ->label('Campista')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('form_data.telefone_campista')
                    ->label('Telefone')
                    ->visibleFrom('lg'),

                TextColumn::make('status')
                    ->badge()
                    ->alignCenter()
                    ->label('Status'),

                TextColumn::make('forma_pagamento')
                    ->badge()
                    ->alignCenter()
                    ->label('Pagamento')
                    ->visibleFrom('md'),

                IconColumn::make('presenca')
                    ->label('Presença')
                    ->alignCenter()
                    ->boolean()
                    ->visibleFrom('md'),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Action::make('view')
                    ->label('Visualizar inscrição')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->tooltip('Visualizar inscrição')
                    ->url(fn (Campista $record): string => CampistaResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Nenhum campista nesta tribo')
            ->emptyStateDescription('Campistas aparecem aqui quando a inscrição está vinculada a esta tribo.')
            ->defaultSort('nome');
    }
}
