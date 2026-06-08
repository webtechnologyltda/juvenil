<?php

use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Pages\FinancialDashboard;
use App\Filament\Widgets\Financial\FinancialCategoryChart;
use App\Filament\Widgets\Financial\FinancialDailyFlowChart;
use App\Filament\Widgets\Financial\FinancialOverviewStats;
use App\Filament\Widgets\Financial\RecentFinancialEntriesTable;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function financialDashboardChartOptions(string $chart): array
{
    $method = new ReflectionMethod($chart, 'getOptions');
    $method->setAccessible(true);

    return $method->invoke(app($chart));
}

function financialDashboardChartExtraJs(string $chart): string
{
    $method = new ReflectionMethod($chart, 'extraJsOptions');
    $method->setAccessible(true);

    return (string) $method->invoke(app($chart));
}

function makeFinancialDashboardWidgetEntry(array $attributes = []): Lancamento
{
    return Lancamento::query()->create(array_merge([
        'nome' => 'Lançamento financeiro',
        'descricao' => null,
        'comprador' => null,
        'data' => '2026-06-07 10:00:00',
        'valor' => 10000,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => 1,
        'comprovante' => [],
        'categoria_lancamento_id' => null,
        'user_id' => null,
    ], $attributes));
}

it('registers a financial dashboard page in the finance navigation group with global filters', function () {
    $page = file_get_contents(app_path('Filament/Pages/FinancialDashboard.php'));

    expect(FinancialDashboard::getFinancialWidgets())->toBe([
        FinancialOverviewStats::class,
        FinancialDailyFlowChart::class,
        FinancialCategoryChart::class,
        RecentFinancialEntriesTable::class,
    ])
        ->and(FinancialDashboard::getRoutePath(filament()->getPanel('admin')))->toBe('/financeiro')
        ->and($page)
        ->toContain("protected static string|\\UnitEnum|null \$navigationGroup = 'Financeiro'")
        ->toContain("DatePicker::make('data_inicio')")
        ->toContain("DatePicker::make('data_fim')")
        ->toContain("Select::make('tipo')")
        ->toContain("Select::make('status')")
        ->toContain("Select::make('categoria_lancamento_id')")
        ->toContain("Select::make('forma_pagamento')");
});

it('renders the financial dashboard route for administrators', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $this->actingAs($user)
        ->get(route('filament.admin.pages.financial-dashboard'))
        ->assertOk();
});

it('configures financial Apex charts with currency formatters', function () {
    $dailyOptions = financialDashboardChartOptions(FinancialDailyFlowChart::class);
    $categoryOptions = financialDashboardChartOptions(FinancialCategoryChart::class);

    expect($dailyOptions['chart']['type'])->toBe('bar')
        ->and($dailyOptions['series'])->toHaveCount(4)
        ->and($categoryOptions['chart']['type'])->toBe('pie')
        ->and($categoryOptions)->toHaveKey('labels')
        ->and($categoryOptions)->not->toHaveKey('xaxis')
        ->and($categoryOptions)->not->toHaveKey('plotOptions')
        ->and(financialDashboardChartExtraJs(FinancialDailyFlowChart::class))->toContain('Intl.NumberFormat')
        ->and(financialDashboardChartExtraJs(FinancialCategoryChart::class))->toContain('Intl.NumberFormat')
        ->and(financialDashboardChartExtraJs(FinancialCategoryChart::class))->toContain('options.w.config.series');
});

it('renders financial category values as pie chart slices', function () {
    $market = CategoriaLancamento::query()->create([
        'nome' => 'Mercado',
        'tipo' => TipoLacamento::Despesa->value,
        'cor' => '#4f18ff',
        'icone' => 'heroicon-o-shopping-cart',
        'ativo' => true,
    ]);

    makeFinancialDashboardWidgetEntry([
        'valor' => 560,
        'tipo' => TipoLacamento::Despesa->value,
        'categoria_lancamento_id' => $market->id,
    ]);

    makeFinancialDashboardWidgetEntry([
        'valor' => 347,
        'categoria_lancamento_id' => null,
    ]);

    $options = financialDashboardChartOptions(FinancialCategoryChart::class);

    expect($options['chart']['type'])->toBe('pie')
        ->and($options['labels'])->toBe(['Mercado', 'Sem categoria'])
        ->and($options['series'])->toBe([560, 347]);
});

it('keeps the daily financial flow y axis able to show negative balances', function () {
    makeFinancialDashboardWidgetEntry([
        'valor' => 2000,
        'tipo' => TipoLacamento::Receita->value,
    ]);
    makeFinancialDashboardWidgetEntry([
        'valor' => 5000,
        'tipo' => TipoLacamento::Despesa->value,
    ]);

    $options = financialDashboardChartOptions(FinancialDailyFlowChart::class);

    expect($options['yaxis']['min'])->toBe(-3000);
});
