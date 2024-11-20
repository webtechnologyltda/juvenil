<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\RoleEnum;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Table;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    protected static ?string $title = 'Grupos de Permissões';

    protected static ?string $label = 'Grupo de Permissão';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->formatStateUsing(fn ($record) => RoleEnum::getRoleEnumDescriptionById($record->id))
                    ->label('Grupo de Permissão'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->recordSelect(
                        fn (Select $select) => $select->placeholder('Selecione um Perfil'),
                    )
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->hidden(fn ($record) => ($record->id === RoleEnum::Financeiro->value
                        && ($record->user_id === auth()->user()->id || $record->user_id === 1))),
            ])
            ->bulkActions([])
            ->emptyStateActions([
                Tables\Actions\AttachAction::make()->preloadRecordSelect(),
            ]);
    }
}
