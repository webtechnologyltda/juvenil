<?php

namespace App\Filament\Resources;

use App\Filament\Exports\EquipeTrabalhoExporter;
use App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoForm;
use App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoTable;
use App\Filament\Resources\EquipeTrabalhoResource\Pages;
use App\Filament\Resources\EquipeTrabalhoResource\Widgets\EquipeTrabalhoStatsWidget;
use App\Models\EquipeTrabalho;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EquipeTrabalhoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = EquipeTrabalho::class;

    protected static string|\BackedEnum|null $navigationIcon = 'ri-team-fill';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Acampamento';

    protected static ?string $label = 'Inscrição - Equipe de Trabalho';

    protected static ?string $pluralLabel = 'Inscrições - Equipe de Trabalho';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components(
                EquipeTrabalhoForm::getFormUpdate(),
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->select([
                'id',
                'nome',
                'avatar_url',
                'data_form',
                'status',
                'tribo_id',
                'descricao',
                'tipo_equipe',
                'created_at',
            ]))
            ->columns(
                EquipeTrabalhoTable::getColumns()
            )
            ->filters([
                ...EquipeTrabalhoTable::getFilters(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->visible(fn (): bool => auth()->user()->can('export', EquipeTrabalho::class))
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
            'view' => Pages\ViewEquipeTrabalho::route('/{record}'),
            'edit' => Pages\EditEquipeTrabalho::route('/{record}/edit'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'delete_any',
            'restore',
            'force_delete',
            'force_delete_any',
            'restore_any',
            'replicate',
            'reorder',
            'export',
        ];
    }

    public static function getWidgets(): array
    {
        return [
            EquipeTrabalhoStatsWidget::class,
        ];
    }
}
