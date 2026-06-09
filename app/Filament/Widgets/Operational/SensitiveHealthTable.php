<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use App\Models\Campista;
use App\Support\Tribes\TribeColor;
use Carbon\Carbon;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class SensitiveHealthTable extends TableWidget
{
    use UsesOperationalDashboardData;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    public static function canView(): bool
    {
        $user = Filament::auth()?->user();

        return parent::canView() && $user?->can('view_sensitive_health_campista');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Enfermaria')
            ->description('Lista curta para triagem. O texto completo fica na ficha do campista.')
            ->query(
                $this->campistaQuery()
                    ->where(function (Builder $query): void {
                        $this->applyTruthyConditions($query, [
                            'form_data->toma_remedio',
                            'form_data->tem_recomendacao',
                        ]);
                    })
            )
            ->defaultSort('nome')
            ->actions([
                ViewAction::make()
                    ->label('Ver ficha')
                    ->url(fn (Campista $record): string => route('filament.admin.resources.campistas.view', $record->id)),
            ])
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tribo.cor')
                    ->label('Tribo')
                    ->placeholder('Sem tribo')
                    ->formatStateUsing(fn (?string $state, Campista $record) => TribeColor::badge($record->tribo))
                    ->html(),
                TextColumn::make('form_data.data_nacimento')
                    ->label('Idade')
                    ->alignCenter()
                    ->formatStateUsing(fn (?string $state): string => $this->ageFromDate($state)),
                IconColumn::make('form_data.toma_remedio')
                    ->label('Remédio')
                    ->alignCenter()
                    ->boolean(),
                IconColumn::make('form_data.tem_recomendacao')
                    ->label('Recomendação')
                    ->alignCenter()
                    ->boolean(),
            ]);
    }

    private function applyTruthyConditions(Builder $query, array $columns): void
    {
        $first = true;

        foreach ($columns as $column) {
            foreach ([true, 1, '1', 'true', 'sim', 'yes', 'on'] as $value) {
                $method = $first ? 'where' : 'orWhere';
                $query->{$method}($column, $value);
                $first = false;
            }
        }
    }

    private function ageFromDate(?string $date): string
    {
        if (blank($date)) {
            return '-';
        }

        try {
            $birthDate = Carbon::createFromFormat('!d/m/Y', $date);
        } catch (\Throwable) {
            return '-';
        }

        return $birthDate === null || $birthDate->format('d/m/Y') !== $date
            ? '-'
            : (string) $birthDate->age;
    }
}
