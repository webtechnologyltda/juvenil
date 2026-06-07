<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use App\Models\Campista;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OperationalPendingTasksTable extends TableWidget
{
    use UsesOperationalDashboardData;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 9;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pendências operacionais')
            ->description('Dados faltantes para contato, comunidade, camiseta ou foto. Tribo não entra como dívida do campista.')
            ->query(
                $this->campistaQuery()
                    ->where(function (Builder $query): void {
                        $this->applyBlankConditions($query, [
                            'avatar_url',
                            'form_data->telefone_campista',
                            'form_data->telefone_reponsavel_1',
                            'form_data->telefone_reponsavel_nome_1',
                            'form_data->paroquia',
                            'form_data->comunidade',
                            'form_data->tamanho_camiseta',
                        ]);
                    })
            )
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make()
                    ->label('Ver inscrição')
                    ->url(fn (Campista $record): string => route('filament.admin.resources.campistas.view', $record->id)),
            ])
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('tribo.cor')
                    ->label('Tribo')
                    ->placeholder('Sem tribo')
                    ->badge(),
                TextColumn::make('pending_issues')
                    ->label('Pendências')
                    ->state(fn (Campista $record): string => $this->pendingIssues($record))
                    ->wrap(),
            ]);
    }

    private function applyBlankConditions(Builder $query, array $columns): void
    {
        $first = true;

        foreach ($columns as $column) {
            $method = $first ? 'whereNull' : 'orWhereNull';
            $query->{$method}($column);
            $query->orWhere($column, '');

            $first = false;
        }
    }

    private function pendingIssues(Campista $record): string
    {
        $issues = collect([
            blank(data_get($record->form_data, 'telefone_campista')) ? 'Sem telefone do campista' : null,
            blank(data_get($record->form_data, 'telefone_reponsavel_1')) ? 'Sem telefone do responsavel' : null,
            blank(data_get($record->form_data, 'telefone_reponsavel_nome_1')) ? 'Sem nome do responsavel' : null,
            blank(data_get($record->form_data, 'paroquia')) || blank(data_get($record->form_data, 'comunidade')) ? 'Sem paróquia/comunidade' : null,
            blank(data_get($record->form_data, 'tamanho_camiseta')) ? 'Sem tamanho de camiseta' : null,
            blank($record->avatar_url) ? 'Sem foto' : null,
        ])->filter()->values();

        return $issues->isEmpty() ? 'Sem pendência' : $issues->implode(', ');
    }
}
