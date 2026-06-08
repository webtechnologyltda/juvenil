<?php

use App\Enums\StatusInscricao;
use App\Filament\Dashboard;
use App\Filament\Widgets\Operational\CommunityDistributionChart;
use App\Filament\Widgets\Operational\DemographicsChart;
use App\Filament\Widgets\Operational\OperationalFunnelChart;
use App\Filament\Widgets\Operational\OperationalPendingTasksTable;
use App\Filament\Widgets\Operational\OperationalPipelineStats;
use App\Filament\Widgets\Operational\RegistrationTrendChart;
use App\Filament\Widgets\Operational\SensitiveHealthSummaryStats;
use App\Filament\Widgets\Operational\SensitiveHealthTable;
use App\Filament\Widgets\Operational\SexDistributionChart;
use App\Filament\Widgets\Operational\ShirtSizeChart;
use App\Filament\Widgets\Operational\TribeDistributionChart;
use App\Models\Campista;
use App\Models\Tribo;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function operationalDashboardChartOptions(string $chart): array
{
    $method = new ReflectionMethod($chart, 'getOptions');
    $method->setAccessible(true);

    return $method->invoke(app($chart));
}

function operationalDashboardChartExtraJs(string $chart): string
{
    $method = new ReflectionMethod($chart, 'extraJsOptions');
    $method->setAccessible(true);

    return (string) $method->invoke(app($chart));
}

it('registers the operational widgets as the admin dashboard surface', function () {
    expect(Dashboard::getOperationalWidgets())->toBe([
        OperationalPipelineStats::class,
        SensitiveHealthSummaryStats::class,
        OperationalFunnelChart::class,
        RegistrationTrendChart::class,
        TribeDistributionChart::class,
        ShirtSizeChart::class,
        CommunityDistributionChart::class,
        DemographicsChart::class,
        SexDistributionChart::class,
        OperationalPendingTasksTable::class,
        SensitiveHealthTable::class,
    ]);
});

it('renders the operational stats heading without a nested card background', function () {
    $adminTheme = file_get_contents(resource_path('css/filament/admin/theme.css'));

    expect($adminTheme)
        ->toContain('.fi-wi-stats-overview .fi-section-header')
        ->toContain('background: transparent;')
        ->toContain('.fi-wi-stats-overview .fi-section-has-header > .fi-section-content-ctn')
        ->toContain('border-top: 0;');
});

it('limits the sensitive health dashboard table to authorized users', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::findOrCreate('view_sensitive_health_campista');
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $user = User::factory()->create();
    $authorizedUser = User::factory()->create();
    $authorizedUser->givePermissionTo('view_sensitive_health_campista');

    $this->actingAs($user);
    expect(SensitiveHealthTable::canView())->toBeFalse();

    $this->actingAs($authorizedUser);
    expect(SensitiveHealthTable::canView())->toBeTrue();
});

it('renders the admin dashboard with the operational surface', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $this->actingAs($user)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertOk();
});

it('keeps registration trend chart y axis as whole-number counts', function () {
    Campista::factory()
        ->count(2)
        ->create([
            'status' => StatusInscricao::Pago->value,
            'presenca' => false,
            'tribo_id' => null,
            'user_id' => null,
            'created_at' => '2026-06-07 10:00:00',
        ]);

    $options = operationalDashboardChartOptions(RegistrationTrendChart::class);

    expect($options['yaxis'])->toMatchArray([
        'min' => 0,
        'decimalsInFloat' => 0,
        'tickAmount' => 2,
    ]);
});

it('renders tribe distribution as a pie chart', function () {
    $fuchsia = Tribo::query()->create(['cor' => 'Fuchsia']);
    $crimson = Tribo::query()->create(['cor' => 'Crimson']);

    Campista::factory()
        ->count(2)
        ->create([
            'status' => StatusInscricao::Pago->value,
            'presenca' => false,
            'tribo_id' => $fuchsia->id,
            'user_id' => null,
        ]);

    Campista::factory()->create([
        'status' => StatusInscricao::Pago->value,
        'presenca' => false,
        'tribo_id' => $crimson->id,
        'user_id' => null,
    ]);

    $options = operationalDashboardChartOptions(TribeDistributionChart::class);

    expect($options['chart']['type'])->toBe('pie')
        ->and($options['labels'])->toBe(['Fuchsia', 'Crimson'])
        ->and($options['series'])->toBe([2, 1]);
});

it('renders demographics only as ordered age ranges', function () {
    Carbon::setTestNow('2026-06-07 12:00:00');

    foreach ([
        '20/07/1996',
        '15/02/1992',
        '10/03/1988',
        '22/11/1979',
        '04/04/1973',
    ] as $date) {
        Campista::factory()->create([
            'status' => StatusInscricao::Pago->value,
            'presenca' => false,
            'tribo_id' => null,
            'user_id' => null,
            'form_data' => [
                'data_nacimento' => $date,
                'sexo' => 'M',
            ],
        ]);
    }

    $options = operationalDashboardChartOptions(DemographicsChart::class);

    expect($options['xaxis']['categories'])->toBe([
        'Ate 29',
        '30-34',
        '35-39',
        '45-49',
        '50-54',
    ])
        ->and($options['series'][0]['data'])->toBe([1, 1, 1, 1, 1])
        ->and($options['xaxis']['categories'])->not->toContain('Masculino')
        ->and($options['xaxis']['categories'])->not->toContain('Feminino');

    Carbon::setTestNow();
});

it('renders sex distribution as a blue and pink pie chart with totals', function () {
    Campista::factory()
        ->count(2)
        ->create([
            'status' => StatusInscricao::Pago->value,
            'presenca' => false,
            'tribo_id' => null,
            'user_id' => null,
            'form_data' => [
                'sexo' => 'M',
            ],
        ]);

    Campista::factory()->create([
        'status' => StatusInscricao::Pago->value,
        'presenca' => false,
        'tribo_id' => null,
        'user_id' => null,
        'form_data' => [
            'sexo' => 'F',
        ],
    ]);

    $options = operationalDashboardChartOptions(SexDistributionChart::class);

    expect($options['chart']['type'])->toBe('pie')
        ->and($options['labels'])->toBe(['Masculino', 'Feminino'])
        ->and($options['series'])->toBe([2, 1])
        ->and($options['colors'])->toBe(['#2563eb', '#ec4899'])
        ->and(operationalDashboardChartExtraJs(SexDistributionChart::class))->toContain('options.w.config.series[options.seriesIndex]');
});

it('keeps every operational chart configured to render count values without decimals', function () {
    foreach ([
        OperationalFunnelChart::class,
        ShirtSizeChart::class,
        CommunityDistributionChart::class,
        DemographicsChart::class,
    ] as $chart) {
        $options = operationalDashboardChartOptions($chart);

        expect($options['xaxis'])
            ->toHaveKey('decimalsInFloat', 0)
            ->toHaveKey('tickAmount')
            ->toHaveKey('min', 0);
    }

    $trendOptions = operationalDashboardChartOptions(RegistrationTrendChart::class);

    expect($trendOptions['yaxis'])
        ->toHaveKey('decimalsInFloat', 0)
        ->toHaveKey('tickAmount')
        ->toHaveKey('min', 0);
});

it('formats operational chart labels and tooltips without decimal fractions', function () {
    foreach ([
        OperationalFunnelChart::class,
        RegistrationTrendChart::class,
        TribeDistributionChart::class,
        ShirtSizeChart::class,
        CommunityDistributionChart::class,
        DemographicsChart::class,
        SexDistributionChart::class,
    ] as $chart) {
        expect(operationalDashboardChartExtraJs($chart))->toContain('toFixed(0)');
    }
});
