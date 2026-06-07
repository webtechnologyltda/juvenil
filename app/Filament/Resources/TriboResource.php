<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TriboResource\Pages;
use App\Models\Tribo;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class TriboResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Tribo::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-flag';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Acampamento';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('cor')
                    ->label('Cor da Tribo')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Código'),
                TextColumn::make('cor')
                    ->label('Cor da Tribo'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTribos::route('/'),
            'create' => Pages\CreateTribo::route('/create'),
            'edit' => Pages\EditTribo::route('/{record}/edit'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'audit',
            'restoreAudit',
        ];
    }
}
