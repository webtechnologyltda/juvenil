<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TriboResource\Pages;
use App\Filament\Resources\TriboResource\RelationManagers;
use App\Models\Tribo;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class TriboResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Tribo::class;

    protected static ?string $navigationIcon = 'heroicon-s-flag';

    protected static ?string $navigationGroup = 'Gestão Acampamento';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('cor')
                    ->label('Cor da Tribo')
                    ->required()
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
