<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampistaResource\CampistaExport;
use App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoForm;
use App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoTable;
use App\Filament\Resources\EquipeTrabalhoResource\Pages;
use App\Filament\Resources\EquipeTrabalhoResource\Widgets\EquipeTrabalhoStatsWidget;
use App\Models\Campista;
use App\Models\EquipeTrabalho;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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
                ExportBulkAction::make()
                    ->exports([
                        ExcelExport::make()->withColumns([
                            Column::make('id')
                                ->heading('Cod. Inscricão'),

                            Column::make('nome')
                                ->heading('Campista'),


                            Column::make('data_form.data_nacimento')
                                ->formatStateUsing(fn($state) => Carbon::createFromFormat('d/m/Y', $state)->age)
                                ->heading('Idade'),

                            Column::make('data_form.telefone')
                                ->heading('Telefone'),

                            Column::make('data_form.reponsavel_nome')
                                ->heading('Responsável'),

                            Column::make('data_form.reponsavel_telefone')
                                ->heading('Responsável'),

                            Column::make('data_form.tamanho_camiseta')
                                ->heading('Tamanho da Camiseta')
                                ->formatStateUsing(function ($record) {
                                    if ($record->data_form['tamanho_camiseta'] == 'O') {
                                        return $record->data_form['tamanho_camiseta_outro'];
                                    } else {
                                        return $record->data_form['tamanho_camiseta'];
                                    }
                                }),


                            Column::make('data_form.servir_no_acampamento')
                                ->formatStateUsing(fn($state) => $state ? 'Sim' : 'Não')
                                ->heading('Pode servir dentro do acampamento ?'),



                            Column::make('data_form.rua')
                                ->heading('Rua'),

                            Column::make('data_form.numero')
                                ->heading('Numero'),

                            Column::make('data_form.ponto_referencia')
                                ->heading('Ponto Referencia'),

                            Column::make('data_form.bairro')
                                ->heading('Bairro'),

                            Column::make('data_form.cidade')
                                ->heading('Cidade'),

                            Column::make('data_form.ja_participou_retiro')
                                ->heading('Ja Participou de Retiro')
                                ->formatStateUsing(fn($state) => $state ? 'Sim' : 'Não'),

                            Column::make('data_form.pode_missas_diarias')
                                ->heading('Pode participar de Missas Diarias')
                                ->formatStateUsing(fn($state) => $state ? 'Sim' : 'Não'),

                            Column::make('data_form.retiro_que_participou')
                                ->heading('Retiro Que Participou'),

                        ])->askForFilename('equipeTrabalho' . Carbon::now()->format('YmdHis'), 'Informe o nome do arquivo')
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
            'index' => Pages\ListEquipeTrabalhos::route('/'),
            'create' => Pages\CreateEquipeTrabalho::route('/create'),
            'edit' => Pages\EditEquipeTrabalho::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            EquipeTrabalhoStatsWidget::class
        ];
    }
}
