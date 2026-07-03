<?php

namespace App\Filament\Pages;

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Widgets\Financial\FinancialCategoryChart;
use App\Filament\Widgets\Financial\FinancialDailyFlowChart;
use App\Filament\Widgets\Financial\FinancialOverviewStats;
use App\Filament\Widgets\Financial\FinancialPaymentMethodChart;
use App\Filament\Widgets\Financial\RecentFinancialEntriesTable;
use App\Models\CategoriaLancamento;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as FilamentDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class FinancialDashboard extends FilamentDashboard
{
    use HasFiltersForm;
    use HasPageShield;

    protected static string $routePath = '/financeiro';

    protected static ?string $title = 'Dashboard financeiro';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = -1;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('data_inicio')
                    ->label('Início')
                    ->native(false),
                DatePicker::make('data_fim')
                    ->label('Fim')
                    ->native(false),
                Select::make('status')
                    ->label('Status')
                    ->native(false)
                    ->multiple()
                    ->options(StatusLacamento::class)
                    ->default([StatusLacamento::Pago->value])
                    ->placeholder('Pagos'),
                Select::make('tipo')
                    ->label('Tipo de lançamento')
                    ->native(false)
                    ->multiple()
                    ->options(TipoLacamento::class),
                Select::make('categoria_lancamento_id')
                    ->label('Categoria')
                    ->native(false)
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => CategoriaLancamento::query()
                        ->orderBy('nome')
                        ->pluck('nome', 'id')
                        ->all()),
                Select::make('forma_pagamento')
                    ->label('Forma de pagamento')
                    ->native(false)
                    ->multiple()
                    ->options(FormaPagamento::class),
            ]);
    }

    public function getWidgets(): array
    {
        return static::getFinancialWidgets();
    }

    public static function getFinancialWidgets(): array
    {
        return [
            FinancialOverviewStats::class,
            FinancialDailyFlowChart::class,
            FinancialCategoryChart::class,
            FinancialPaymentMethodChart::class,
            RecentFinancialEntriesTable::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'xl' => 2,
        ];
    }
}
