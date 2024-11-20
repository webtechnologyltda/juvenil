<?php

namespace App\Filament\Resources;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Filament\Resources\CampistaResource\CampistaExport;
use App\Filament\Resources\CampistaResource\CampistaForm;
use App\Filament\Resources\CampistaResource\CampistaTable;
use App\Filament\Resources\CampistaResource\Pages;
use App\Models\Campista;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class CampistaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Campista::class;

    protected static ?string $navigationIcon = 'clarity-file-group-line';

    protected static ?string $navigationGroup = 'Gestão Acampamento';

    protected static ?string $label = 'Inscrição';
    protected static ?string $pluralLabel = 'Inscrições';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ...CampistaForm::getFormUpdate()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                CampistaTable::getListTableColumns()
            )
            ->filters([
                SelectFilter::make('status')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->label('Status')
                    ->options(StatusInscricao::class),
                SelectFilter::make('tribo_id')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->selectablePlaceholder('Sem tribo definida')
                    ->label('Tribo')
                    ->relationship('tribo', 'cor'),
                Tables\Filters\TernaryFilter::make('presenca')
                    ->label('Presença')
                    ->placeholder('Todos')
                    ->trueLabel('Presensas confirmadas')
                    ->falseLabel('Presenças pendentes')

            ])
            ->groups([
                Group::make('status')
                    ->label('Status'),
                Group::make('forma_pagamento')
                    ->label('Forma de Pagamento'),
                Group::make('presenca')
                    ->label('Presença'),
                Group::make('tribo.cor')
                    ->label('Tribo'),
            ])
            ->extremePaginationLinks()
            ->deferLoading()
            ->striped()
            ->recordUrl(
                fn(Model $record): string => route('filament.admin.resources.campistas.view', ['record' => $record]),
            )
            ->actions([
                Action::make('Pago')
                    ->action(fn(Campista $record, array $data) => $record->update([

                        $form_temp =  $record->form_data,
                        $form_temp['comprovante_nome'] = $data['comprovante_nome'],
                        $form_temp['comprovante'] = $data['comprovante'],
                        'status' => StatusInscricao::Pago->value,
                        'dia_pagamento' => Carbon::now(),
                        'forma_pagamento' => $data['forma_pagamento'],
                        'observacoes' => $data['observacoes'],
                        'form_data'=>  $form_temp,
                    ]))
                    ->visible(fn(Campista $record) => $record->status == StatusInscricao::Pendente && auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->fillForm(fn(Campista $record): array => [
                        'observacoes' => $record->observacoes,
                    ])
                    ->form([
                        Select::make('forma_pagamento')
                            ->columnSpan(2)
                            ->options(FormaPagamento::class),
                        Textarea::make('observacoes')
                            ->label('Observação')
                            ->rows(5)
                            ->columnSpan(2),
                        TextInput::make('comprovante_nome')
                                            ->label('Nome Comprovante'),
                        FileUpload::make('comprovante')
                                            ->placeholder( 'Tamanho max.: 2MB')
                                            ->hint('Tamanho máximo: 2MB')
                                            ->label('Documento')
                                            ->downloadable()
                                            ->openable()
                                            ->multiple()
                                            ->maxSize(2048)
                                            ->uploadingMessage('Carregando...')
                                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                                            ->previewable(true)
                                            ->columnSpan(2),


                    ])
                    ->color('warning')
                    ->iconButton()
                    ->tooltip('Marcar como pago')
                    ->icon('heroicon-s-credit-card')
                    ->icon('heroicon-s-credit-card'),

                Action::make('Presença')
                    ->action(fn(Campista $record) => $record->update([
                        'presenca' => true,
                    ]))
                    ->modalHeading('Confirmar Presença no Acampamento')
                    ->visible(fn(Campista $record) => !$record->presenca && $record->status == StatusInscricao::Pago && auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->color('success')
                    ->iconButton()
                    ->tooltip('Confirmar Presença no Acampamento')
                    ->icon('heroicon-o-hand-thumb-up')
                    ->modalIcon('heroicon-o-hand-thumb-up'),

                Action::make('Remover Presença')
                    ->action(fn(Campista $record) => $record->update([
                        'presenca' => false,
                    ]))
                    ->modalHeading('Remover Presença no Acampamento')
                    ->visible(fn(Campista $record) => $record->presenca && $record->status == StatusInscricao::Pago && auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->iconButton()
                    ->tooltip('Remover a presença no Acampamento')
                    ->icon('heroicon-o-hand-thumb-down')
                    ->modalIcon('heroicon-o-hand-thumb-down'),

                Action::make('Cancelar')
                    ->action(fn(Campista $record, array $data) => $record->update([
                        'status' => StatusInscricao::Cancelado->value,
                        'observacoes' => $data['observacoes'],
                    ]))
                    ->visible(fn(Campista $record) => $record->status == StatusInscricao::Pendente || $record->status == StatusInscricao::Pago && auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->iconButton()
                    ->tooltip('Cancelar Inscrição')
                    ->fillForm(fn(Campista $record): array => [
                        'observacoes' => $record->observacoes,
                    ])
                    ->form([
                        Textarea::make('observacoes')
                            ->label('Observação')
                            ->placeholder('Motivo do Cancelamento')
                            ->rows(5)
                            ->columnSpan(2),
                    ])
                    ->icon('heroicon-s-arrow-left-on-rectangle')
                    ->icon('heroicon-s-arrow-left-on-rectangle'),
                EditAction::make()
                    ->label('Editar')
            ])
            ->paginationPageOptions([5, 10, 30, 50])
            ->extremePaginationLinks()
            ->defaultSort('id', 'desc')
            ->bulkActions([
                ExportBulkAction::make()
                    ->visible(fn(): bool => auth()->user()->can('export', Campista::class))
                    ->exports([
                        ExcelExport::make()->withColumns([
                            ...CampistaExport::getExportColumns()
                        ])->askForFilename('campista' . Carbon::now()->format('YmdHis'), 'Informe o nome do arquivo')
                            ->askForWriterType(Excel::XLSX, label: 'Tipo'),
                    ])->label('Exportar para Excel'),
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
            'index' => Pages\ListCampistas::route('/'),
            'create' => Pages\CreateCampista::route('/create'),
            'edit' => Pages\EditCampista::route('/{record}/edit'),
            'view' => Pages\ViewCampista::route('/{record}'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'updateTribo',
            'delete',
            'delete_any',
            'audit',
            'restoreAudit',
            'export',
        ];
    }
}
