<?php

namespace App\Filament\Resources;

use App\Enums\TipoLacamento;
use App\Filament\Exports\LancamentoExporter;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Filament\Resources\LancamentoResource\Pages;
use App\Filament\Resources\LancamentoResource\Widgets\StatsFinanceiro;
use App\Models\Lancamento;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LancamentoResource extends Resource
{
    protected static ?string $model = Lancamento::class;

    protected static string|\BackedEnum|null $navigationIcon = 'clarity-file-group-line';

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $label = 'Lançamento';

    protected static ?string $pluralLabel = 'Lançamentos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components(LancamentoForm::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Código')
                    ->searchable(),

                TextColumn::make('nome')
                    ->label('Nome do Lançamento')
                    ->searchable(),

                TextColumn::make('valor')
                    ->prefix(fn (Lancamento $record) => ($record->tipo == TipoLacamento::Despesa ? '-' : '').'R$ ')
                    ->label('Valor')
                    ->formatStateUsing(fn (int $state, Lancamento $record) => number_format($state / ($record->tipo == TipoLacamento::Despesa ? -100 : 100), 2, ',', '.'))
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->numeric()
                            ->label('Total')
                            ->money('BRL', locale: 'pt_BR', divideBy: 100)
                    )
                    ->sortable(),

                TextColumn::make('tipo')
                    ->badge()
                    ->alignCenter()
                    ->label('Lançamento'),

                TextColumn::make('status')
                    ->badge()
                    ->alignCenter()
                    ->label('Status'),

                TextColumn::make('data')
                    ->alignCenter()
                    ->label('Data lançamento')
                    ->dateTime('d/m/Y')
                    ->sortable(),

            ])
            ->groups([
                Tables\Grouping\Group::make('status')->collapsible(),
                Tables\Grouping\Group::make('tipo')->collapsible(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exporter(LancamentoExporter::class)
                    ->fileName(fn (Export $export): string => 'lancamento-financeiro-'.Carbon::now()->format('YmdHis').'-'.$export->getKey())
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
            'index' => Pages\ListLancamentos::route('/'),
            'create' => Pages\CreateLancamento::route('/create'),
            'edit' => Pages\EditLancamento::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            StatsFinanceiro::class,
        ];
    }
}
