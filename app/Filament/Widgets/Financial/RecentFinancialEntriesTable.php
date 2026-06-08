<?php

namespace App\Filament\Widgets\Financial;

use App\Enums\TipoLacamento;
use App\Filament\Widgets\Financial\Concerns\UsesFinancialDashboardData;
use App\Models\Lancamento;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentFinancialEntriesTable extends TableWidget
{
    use UsesFinancialDashboardData;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Lançamentos recentes')
            ->description('Lista curta seguindo os filtros globais do dashboard financeiro.')
            ->query(
                $this->financialQuery()
                    ->limit(8)
            )
            ->defaultSort('data', 'desc')
            ->actions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar lançamento')
                    ->url(fn (Lancamento $record): string => route('filament.admin.resources.lancamentos.edit', $record)),
            ])
            ->columns([
                TextColumn::make('data')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('nome')
                    ->label('Lançamento')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->alignCenter(),
                TextColumn::make('categoria.nome')
                    ->label('Categoria')
                    ->placeholder('Sem categoria')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->alignCenter(),
                TextColumn::make('valor')
                    ->label('Valor')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state, Lancamento $record): string => $this->money(
                        $record->tipo === TipoLacamento::Despesa ? -abs($state) : abs($state),
                    )),
            ]);
    }
}
