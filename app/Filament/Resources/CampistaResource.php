<?php

namespace App\Filament\Resources;

use App\Actions\Campistas\CancelCampistaRegistrationAction;
use App\Enums\StatusInscricao;
use App\Filament\Exports\CampistaExporter;
use App\Filament\Resources\CampistaResource\CampistaForm;
use App\Filament\Resources\CampistaResource\CampistaTable;
use App\Filament\Resources\CampistaResource\Pages;
use App\Models\Campista;
use App\Support\Financeiro\FinancialFilterOptions;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class CampistaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Campista::class;

    protected static string|\BackedEnum|null $navigationIcon = 'clarity-file-group-line';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Acampamento';

    protected static ?string $label = 'Inscrição';

    protected static ?string $pluralLabel = 'Inscrições';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                ...CampistaForm::getFormUpdate(),
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
                SelectFilter::make('payment_status')
                    ->label('Status do pagamento')
                    ->options(fn (): array => FinancialFilterOptions::paymentStatuses())
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        $statuses = FinancialFilterOptions::selectedIntegerValues($data);

                        return $statuses === []
                            ? $query
                            : $query->whereHas(
                                'lancamentoItems.lancamento',
                                fn (Builder $query): Builder => $query->whereIn('status', $statuses),
                            );
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->modifyFormFieldUsing(fn (Select $field): Select => $field->allowHtml())
                    ->indicateUsing(fn (array $state): array => FinancialFilterOptions::paymentStatusIndicators($state)),
                SelectFilter::make('categoria_lancamento_id')
                    ->label('Categoria')
                    ->options(fn (): array => FinancialFilterOptions::categories())
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        $categoryIds = FinancialFilterOptions::selectedIntegerValues($data);

                        return $categoryIds === []
                            ? $query
                            : $query->whereHas(
                                'lancamentoItems',
                                fn (Builder $query): Builder => $query->whereIn('categoria_lancamento_id', $categoryIds),
                            );
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->modifyFormFieldUsing(fn (Select $field): Select => $field->allowHtml())
                    ->indicateUsing(fn (array $state): array => FinancialFilterOptions::categoryIndicators($state)),
                Tables\Filters\TernaryFilter::make('presenca')
                    ->label('Presença')
                    ->placeholder('Todos')
                    ->trueLabel('Presensas confirmadas')
                    ->falseLabel('Presenças pendentes'),

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
                fn (Model $record): string => route('filament.admin.resources.campistas.view', ['record' => $record]),
            )
            ->actions([
                Action::make('Presença')
                    ->action(fn (Campista $record) => $record->update([
                        'presenca' => true,
                    ]))
                    ->modalHeading('Confirmar Presença no Acampamento')
                    ->visible(fn (Campista $record) => ! $record->presenca && $record->status == StatusInscricao::Pago && auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->color('success')
                    ->iconButton()
                    ->tooltip('Confirmar Presença no Acampamento')
                    ->icon('heroicon-o-hand-thumb-up')
                    ->modalIcon('heroicon-o-hand-thumb-up'),

                Action::make('Remover Presença')
                    ->action(fn (Campista $record) => $record->update([
                        'presenca' => false,
                    ]))
                    ->modalHeading('Remover Presença no Acampamento')
                    ->visible(fn (Campista $record) => $record->presenca && $record->status == StatusInscricao::Pago && auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->iconButton()
                    ->tooltip('Remover a presença no Acampamento')
                    ->icon('heroicon-o-hand-thumb-down')
                    ->modalIcon('heroicon-o-hand-thumb-down'),

                Action::make('Cancelar')
                    ->action(fn (
                        Campista $record,
                        array $data,
                        CancelCampistaRegistrationAction $cancelRegistration,
                    ) => $cancelRegistration->execute(
                        campista: $record,
                        reason: $data['observacoes'] ?? null,
                        paymentAction: $data['payment_action'] ?? null,
                    ))
                    ->visible(fn (Campista $record): bool => in_array($record->status, [
                        StatusInscricao::Pendente,
                        StatusInscricao::Pago,
                    ], true) && auth()->user()->can('update', $record))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->iconButton()
                    ->tooltip('Cancelar Inscrição')
                    ->modalHeading(fn (
                        Campista $record,
                        CancelCampistaRegistrationAction $cancelRegistration,
                    ): string => $cancelRegistration->hasPaidPayment($record)
                        ? 'Cancelar inscrição paga'
                        : 'Cancelar inscrição')
                    ->modalDescription(fn (
                        Campista $record,
                        CancelCampistaRegistrationAction $cancelRegistration,
                    ): string => $cancelRegistration->hasPaidPayment($record)
                        ? 'Informe o motivo e escolha o que deve acontecer com o pagamento já confirmado.'
                        : 'Informe o motivo do cancelamento da inscrição.')
                    ->modalSubmitActionLabel('Confirmar cancelamento')
                    ->closeModalByClickingAway(false)
                    ->fillForm(fn (Campista $record): array => [
                        'observacoes' => $record->observacoes,
                        'payment_action' => null,
                    ])
                    ->schema(fn (
                        Campista $record,
                        CancelCampistaRegistrationAction $cancelRegistration,
                    ): array => [
                        Textarea::make('observacoes')
                            ->label('Observação')
                            ->placeholder('Motivo do cancelamento')
                            ->rows(5)
                            ->columnSpan(2),
                        ...($cancelRegistration->hasPaidPayment($record) ? [
                            Radio::make('payment_action')
                                ->label('O que deseja fazer com o pagamento?')
                                ->options([
                                    CancelCampistaRegistrationAction::PAYMENT_REFUND => 'Cancelar o pagamento e registrar o estorno',
                                    CancelCampistaRegistrationAction::PAYMENT_KEEP_PAID => 'Manter o pagamento como pago (sem estorno)',
                                ])
                                ->descriptions([
                                    CancelCampistaRegistrationAction::PAYMENT_REFUND => 'O lançamento será cancelado e receberá uma observação com a quantia estornada.',
                                    CancelCampistaRegistrationAction::PAYMENT_KEEP_PAID => 'A inscrição será cancelada, mas o lançamento continuará com status pago.',
                                ])
                                ->required()
                                ->columnSpanFull(),
                        ] : []),
                    ])
                    ->icon('heroicon-s-arrow-left-on-rectangle'),
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
            ])
            ->paginationPageOptions([5, 10, 30, 50])
            ->extremePaginationLinks()
            ->defaultSort('id', 'desc')
            ->toolbarActions([
                ExportBulkAction::make()
                    ->visible(fn (): bool => auth()->user()->can('export', Campista::class))
                    ->exporter(CampistaExporter::class)
                    ->fileName(fn (Export $export): string => 'campista-'.Carbon::now()->format('YmdHis').'-'.$export->getKey())
                    ->label('Exportar'),
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
