<?php

namespace App\Filament\Resources;

use App\Filament\Exports\EquipeTrabalhoExporter;
use App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoForm;
use App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoTable;
use App\Filament\Resources\EquipeTrabalhoResource\Pages;
use App\Filament\Resources\EquipeTrabalhoResource\Widgets\EquipeTrabalhoStatsWidget;
use App\Models\EquipeTrabalho;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class EquipeTrabalhoResource extends Resource
{
    protected static ?string $model = EquipeTrabalho::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-team-fill';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Acampamento';

    protected static ?string $label = 'Inscrição - Equipe de Trabalho';

    protected static ?string $pluralLabel = 'Inscrições - Equipe de Trabalho';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
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
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exporter(EquipeTrabalhoExporter::class)
                    ->fileName(fn (Export $export): string => 'equipe-trabalho-'.Carbon::now()->format('YmdHis').'-'.$export->getKey())
                    ->label('Exportar'),
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

    public static function getWidgets(): array
    {
        return [
            EquipeTrabalhoStatsWidget::class,
        ];
    }
}
