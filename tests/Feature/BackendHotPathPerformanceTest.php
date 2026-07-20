<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Resources\CampistaResource\Pages\ListCampistas;
use App\Filament\Resources\EquipeTrabalhoResource\Widgets\EquipeTrabalhoStatsWidget;
use App\Filament\Resources\LancamentoResource\Widgets\StatsFinanceiro;
use App\Jobs\ReconcileRegistrationLaunchesJob;
use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Settings\GeneralSettings;
use App\Support\Financeiro\AutomaticRegistrationLaunchService;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

it('dispatches asynchronous reconciliation without calculating a synchronous preview', function () {
    Queue::fake();

    $service = mock(AutomaticRegistrationLaunchService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('preview');
        $mock->shouldNotReceive('reconcile');
    });

    $this->app->instance(AutomaticRegistrationLaunchService::class, $service);

    $this->artisan('financeiro:reconcile-registration-launches --type=campista')
        ->expectsOutput('Job de regularização automática despachado.')
        ->assertSuccessful();

    Queue::assertPushed(
        ReconcileRegistrationLaunchesJob::class,
        fn (ReconcileRegistrationLaunchesJob $job): bool => $job->type === AutomaticRegistrationLaunchService::TYPE_CAMPISTA,
    );
});

it('previews a registration batch with a bounded query count', function () {
    DB::table('settings')->updateOrInsert(
        ['group' => GeneralSettings::group(), 'name' => 'valor_acampamento'],
        ['payload' => json_encode(35000), 'locked' => false, 'created_at' => now(), 'updated_at' => now()],
    );

    Campista::withoutEvents(fn () => Campista::factory()
        ->count(25)
        ->create([
            'status' => StatusInscricao::Pendente->value,
            'tribo_id' => null,
            'user_id' => null,
        ]));

    app()->forgetInstance(GeneralSettings::class);
    DB::flushQueryLog();
    DB::enableQueryLog();

    $summary = app(AutomaticRegistrationLaunchService::class)
        ->preview(AutomaticRegistrationLaunchService::TYPE_CAMPISTA);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($summary[AutomaticRegistrationLaunchService::TYPE_CAMPISTA])
        ->toMatchArray([
            'candidates' => 25,
            'created' => 25,
            'reactivated' => 0,
            'cancelled' => 0,
            'skipped' => 0,
            'failed' => 0,
        ])
        ->and(count($queries))->toBeLessThanOrEqual(7);
});

it('loads campista tab counts with one aggregate query', function () {
    Campista::withoutEvents(function (): void {
        foreach ([StatusInscricao::Pendente, StatusInscricao::Pago, StatusInscricao::Cancelado] as $status) {
            Campista::factory()->create([
                'status' => $status->value,
                'tribo_id' => null,
                'user_id' => null,
            ]);
        }
    });

    DB::flushQueryLog();
    DB::enableQueryLog();

    $tabs = (new ListCampistas)->getTabs();
    $campistaQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'campistas'));

    DB::disableQueryLog();

    expect($campistaQueries)->toHaveCount(1)
        ->and($tabs['Todas']->getBadge())->toBe('3')
        ->and($tabs[StatusInscricao::Pendente->name]->getBadge())->toBe('1')
        ->and($tabs[StatusInscricao::Pago->name]->getBadge())->toBe('1')
        ->and($tabs[StatusInscricao::Cancelado->name]->getBadge())->toBe('1');
});

it('loads the financial overview with one aggregate query', function () {
    foreach ([
        [TipoLacamento::Receita, 10000],
        [TipoLacamento::Doacao, 2500],
        [TipoLacamento::Despesa, -3000],
    ] as [$type, $amount]) {
        Lancamento::factory()->create([
            'valor' => $amount,
            'tipo' => $type->value,
            'status' => StatusLacamento::Pago->value,
            'forma_pagamento' => FormaPagamento::Pix->value,
            'user_id' => null,
        ]);
    }

    $widget = new class extends StatsFinanceiro
    {
        /**
         * @return array<int, Stat>
         */
        public function stats(): array
        {
            return $this->getStats();
        }
    };

    DB::flushQueryLog();
    DB::enableQueryLog();

    $stats = $widget->stats();
    $lancamentoQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'lancamentos'));

    DB::disableQueryLog();

    expect($lancamentoQueries)->toHaveCount(1)
        ->and($stats[0]->getValue())->toBe('R$ 100,00')
        ->and($stats[1]->getValue())->toBe('-R$ 30,00')
        ->and($stats[2]->getValue())->toBe('R$ 25,00')
        ->and($stats[3]->getValue())->toBe('R$ 95,00');
});

it('loads the team overview with one aggregate query while ignoring missing sex data', function () {
    foreach ([
        [['sexo' => 'F'], StatusInscricaoEquipeTrabalho::Pendente],
        [['sexo' => 'M'], StatusInscricaoEquipeTrabalho::Aprovado],
        [[], StatusInscricaoEquipeTrabalho::Aprovado],
        [['sexo' => 'F'], StatusInscricaoEquipeTrabalho::Cancelado],
    ] as [$formData, $status]) {
        EquipeTrabalho::withoutEvents(fn () => EquipeTrabalho::factory()->create([
            'data_form' => $formData,
            'status' => $status->value,
            'tribo_id' => null,
        ]));
    }

    $widget = new class extends EquipeTrabalhoStatsWidget
    {
        /**
         * @return array<int, Stat>
         */
        public function stats(): array
        {
            return $this->getStats();
        }
    };

    DB::flushQueryLog();
    DB::enableQueryLog();

    $stats = $widget->stats();
    $teamQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'equipe_trabalho'));

    DB::disableQueryLog();

    expect($teamQueries)->toHaveCount(1)
        ->and($stats[0]->getValue())->toBe(3)
        ->and($stats[1]->getValue())->toBe(1)
        ->and($stats[2]->getValue())->toBe(1);
});
