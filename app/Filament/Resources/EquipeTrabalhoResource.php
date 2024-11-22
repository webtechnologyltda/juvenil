<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoForm;
use App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoTable;
use App\Filament\Resources\EquipeTrabalhoResource\Pages;
use App\Filament\Resources\EquipeTrabalhoResource\RelationManagers;
use App\Models\EquipeTrabalho;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipeTrabalhoResource extends Resource
{
    protected static ?string $model = EquipeTrabalho::class;

    protected static ?string $navigationIcon = 'ri-team-fill';

    protected static ?string $navigationGroup = 'Gestão Acampamento';

    protected static ?string $label = 'Inscrição - Equipe de Trabalho';
    protected static ?string $pluralLabel = 'Inscrições - Equipe de Trabalho';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(
                EquipeTrabalhoForm::getFormUpdate(),
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                EquipeTrabalhoTable::getColumns()
            )
            ->filters([
                //
            ])
            ->defaultSort('created_at', 'desc')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipeTrabalhos::route('/'),
            'create' => Pages\CreateEquipeTrabalho::route('/create'),
            'edit' => Pages\EditEquipeTrabalho::route('/{record}/edit'),
        ];
    }
}
