<?php

namespace App\Filament\Resources;

use App\Enums\StatusInscricao;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Resources\CampistaResource\CampistaExport;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Filament\Resources\LancamentoResource\LancamentoExport;
use App\Filament\Resources\LancamentoResource\Pages;
use App\Filament\Resources\LancamentoResource\Widgets\StatsFinanceiro;
use App\Models\Campista;
use App\Models\Lancamento;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class LancamentoResource extends Resource
{
    protected static ?string $model = Lancamento::class;

    protected static ?string $navigationIcon = 'clarity-file-group-line';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?string $label = 'Lançamento';
    protected static ?string $pluralLabel = 'Lançamentos';

    public static function form(Form $form): Form
    {
        return $form->schema(LancamentoForm::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Código')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome do Lançamento')
                    ->searchable(),

                TextColumn::make('valor')
                    ->prefix( fn(Lancamento $record) => ($record->tipo == TipoLacamento::Despesa ? '-' : '') . 'R$ ')
                    ->label('Valor')
                    ->formatStateUsing( fn (int $state, Lancamento $record) => number_format($state / ($record->tipo == TipoLacamento::Despesa ? -100 : 100), 2, ',', '.'))
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
                    ->label( 'Data lançamento' )
                    ->dateTime( 'd/m/Y' )
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->exports([
                        ExcelExport::make()->withColumns([
                            ...LancamentoExport::getExportColumns()
                        ])->askForFilename('Lançamento financeiro' . Carbon::now()->format('YmdHis'), 'Informe o nome do arquivo')
                            ->askForWriterType(Excel::XLSX, label: 'Tipo'),
                    ])->label('Exportar para Excel'),
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
            StatsFinanceiro::class
        ];
    }
}
