<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\StatusLacamento;
use App\Enums\TipoEquipeTrabalho;
use App\Enums\TipoLacamento;
use App\Jobs\CancelRegistrationLaunchJob;
use App\Jobs\EnsureRegistrationLaunchJob;
use App\Jobs\ReconcileRegistrationLaunchesJob;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Support\Financeiro\AutomaticRegistrationLaunchService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches observer jobs when registrations are created cancelled and reactivated', function () {
    Queue::fake();

    $campista = Campista::factory()->create([
        'nome' => 'Observer Campista',
        'status' => StatusInscricao::Pendente->value,
        'forma_pagamento' => FormaPagamento::NaoPago->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    Queue::assertPushed(EnsureRegistrationLaunchJob::class, fn (EnsureRegistrationLaunchJob $job): bool => $job->registrationType === Campista::class
        && $job->registrationId === $campista->id
        && $job->originContext === Lancamento::ORIGIN_CONTEXT_OBSERVER);

    $cancelledAtCreation = Campista::factory()->create([
        'nome' => 'Observer Cancelado',
        'status' => StatusInscricao::Cancelado->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    Queue::assertNotPushed(EnsureRegistrationLaunchJob::class, fn (EnsureRegistrationLaunchJob $job): bool => $job->registrationId === $cancelledAtCreation->id);

    $campista->update(['status' => StatusInscricao::Cancelado->value]);

    Queue::assertPushed(CancelRegistrationLaunchJob::class, fn (CancelRegistrationLaunchJob $job): bool => $job->registrationType === Campista::class
        && $job->registrationId === $campista->id);

    $campista->update(['status' => StatusInscricao::Pendente->value]);

    Queue::assertPushed(EnsureRegistrationLaunchJob::class, fn (EnsureRegistrationLaunchJob $job): bool => $job->registrationType === Campista::class
        && $job->registrationId === $campista->id);

    $equipe = EquipeTrabalho::factory()->create([
        'nome' => 'Observer Equipe',
        'status' => StatusInscricaoEquipeTrabalho::Pendente->value,
        'tribo_id' => null,
    ]);

    Queue::assertPushed(EnsureRegistrationLaunchJob::class, fn (EnsureRegistrationLaunchJob $job): bool => $job->registrationType === EquipeTrabalho::class
        && $job->registrationId === $equipe->id);
});

it('creates one automatic pending launch for a campista without syncing payment status', function () {
    Carbon::setTestNow('2026-06-11 10:00:00');
    seedAutomaticRegistrationAmount(35000);
    CategoriaLancamento::ensureSystemDefaults();

    $campista = automaticRegistrationCampista('Automático Campista');
    $job = new EnsureRegistrationLaunchJob(Campista::class, $campista->id);

    $job->handle(app(AutomaticRegistrationLaunchService::class));
    $job->handle(app(AutomaticRegistrationLaunchService::class));

    $category = automaticRegistrationCategory(CategoriaLancamento::SYSTEM_CATEGORY_INSCRICAO);
    $lancamento = Lancamento::query()->firstOrFail();

    expect(Lancamento::query()->count())->toBe(1)
        ->and($lancamento->nome)->toBe('Inscrição - Automático Campista')
        ->and($lancamento->descricao)->toBe('Lançamento automático gerado a partir da inscrição.')
        ->and(Carbon::parse($lancamento->data)->format('Y-m-d'))->toBe('2026-06-11')
        ->and($lancamento->valor)->toBe(35000)
        ->and($lancamento->tipo)->toBe(TipoLacamento::Receita)
        ->and($lancamento->status)->toBe(StatusLacamento::Pendente)
        ->and($lancamento->forma_pagamento)->toBe(FormaPagamento::NaoPago)
        ->and($lancamento->batch_code)->toBeNull()
        ->and($lancamento->origin)->toBe(Lancamento::ORIGIN_AUTO_REGISTRATION)
        ->and($lancamento->origin_context)->toBe(Lancamento::ORIGIN_CONTEXT_OBSERVER)
        ->and($lancamento->items()->firstOrFail()->categoria_lancamento_id)->toBe($category->id)
        ->and($lancamento->items()->firstOrFail()->registration_type)->toBe(Campista::class)
        ->and($lancamento->items()->firstOrFail()->registration_id)->toBe($campista->id)
        ->and($campista->fresh()->status)->toBe(StatusInscricao::Pendente);

    Carbon::setTestNow();
});

it('creates automatic team work launches with the amount configured for the team type', function () {
    Carbon::setTestNow('2026-06-11 10:00:00');
    seedAutomaticRegistrationAmount(35000, teamInternalAmount: 12000, teamExternalAmount: 8000);
    CategoriaLancamento::ensureSystemDefaults();

    $equipe = automaticRegistrationEquipe('Automático Equipe Externa', [
        'tipo_equipe' => TipoEquipeTrabalho::Externa->value,
    ]);
    $job = new EnsureRegistrationLaunchJob(EquipeTrabalho::class, $equipe->id);

    $job->handle(app(AutomaticRegistrationLaunchService::class));

    $category = automaticRegistrationCategory(CategoriaLancamento::SYSTEM_CATEGORY_CONTRIBUICAO_EQUIPE_TRABALHO);
    $lancamento = Lancamento::query()->firstOrFail();

    expect($lancamento->nome)->toBe('Contribuição equipe - Automático Equipe Externa')
        ->and($lancamento->valor)->toBe(8000)
        ->and($lancamento->items()->firstOrFail()->valor)->toBe(8000)
        ->and($lancamento->items()->firstOrFail()->categoria_lancamento_id)->toBe($category->id)
        ->and($lancamento->items()->firstOrFail()->registration_type)->toBe(EquipeTrabalho::class)
        ->and($lancamento->items()->firstOrFail()->registration_id)->toBe($equipe->id);

    Carbon::setTestNow();
});

it('fails the individual ensure job when the configured amount is zero', function () {
    seedAutomaticRegistrationAmount(0);
    CategoriaLancamento::ensureSystemDefaults();

    $campista = automaticRegistrationCampista('Sem valor configurado');
    $job = new EnsureRegistrationLaunchJob(Campista::class, $campista->id);

    expect(fn () => $job->handle(app(AutomaticRegistrationLaunchService::class)))
        ->toThrow(RuntimeException::class);

    expect(Lancamento::query()->count())->toBe(0);
});

it('reconciles pending campistas and team members into separate daily batches', function () {
    Carbon::setTestNow('2026-06-11 00:00:00');
    seedAutomaticRegistrationAmount(35000);
    CategoriaLancamento::ensureSystemDefaults();

    $campista = automaticRegistrationCampista('Campista Regularização');
    $linkedCampista = automaticRegistrationCampista('Campista Manual');
    $equipe = automaticRegistrationEquipe('Equipe Regularização');
    automaticRegistrationManualLaunch($linkedCampista);

    $this->artisan('financeiro:reconcile-registration-launches --sync')
        ->assertSuccessful();

    $automaticLaunches = Lancamento::query()
        ->where('origin', Lancamento::ORIGIN_AUTO_REGISTRATION)
        ->orderBy('id')
        ->get();

    expect($automaticLaunches)->toHaveCount(2)
        ->and($automaticLaunches->pluck('batch_code')->all())->toBe(['LOTE-20260611-001', 'LOTE-20260611-002'])
        ->and($automaticLaunches->pluck('origin_context')->unique()->values()->all())->toBe([Lancamento::ORIGIN_CONTEXT_DAILY_RECONCILIATION])
        ->and($automaticLaunches->pluck('descricao')->unique()->values()->all())->toBe(['Lançamento automático de regularização diária.'])
        ->and($automaticLaunches->first()->items()->firstOrFail()->registration_id)->toBe($campista->id)
        ->and($automaticLaunches->last()->items()->firstOrFail()->registration_id)->toBe($equipe->id)
        ->and(Lancamento::query()->whereHas('items', fn ($query) => $query
            ->where('registration_type', Campista::class)
            ->where('registration_id', $linkedCampista->id))
            ->count())->toBe(1);

    Carbon::setTestNow();
});

it('cancels only pending exclusive automatic launches for cancelled registrations', function () {
    seedAutomaticRegistrationAmount(35000);
    CategoriaLancamento::ensureSystemDefaults();

    $target = automaticRegistrationCampista('Cancelamento automático');
    $targetLaunch = automaticRegistrationAutomaticLaunch($target);
    automaticRegistrationSetCampistaStatus($target, StatusInscricao::Cancelado);

    (new CancelRegistrationLaunchJob(Campista::class, $target->id))->handle(app(AutomaticRegistrationLaunchService::class));

    $paid = automaticRegistrationCampista('Pago não cancela');
    $paidLaunch = automaticRegistrationAutomaticLaunch($paid, StatusLacamento::Pago);
    automaticRegistrationSetCampistaStatus($paid, StatusInscricao::Cancelado);

    (new CancelRegistrationLaunchJob(Campista::class, $paid->id))->handle(app(AutomaticRegistrationLaunchService::class));

    $manual = automaticRegistrationCampista('Manual não cancela');
    $manualLaunch = automaticRegistrationManualLaunch($manual);
    automaticRegistrationSetCampistaStatus($manual, StatusInscricao::Cancelado);

    (new CancelRegistrationLaunchJob(Campista::class, $manual->id))->handle(app(AutomaticRegistrationLaunchService::class));

    $multi = automaticRegistrationCampista('Multi não cancela');
    $multiLaunch = automaticRegistrationAutomaticLaunch($multi);
    $multiLaunch->items()->create([
        'nome' => 'Outro item',
        'descricao' => null,
        'valor' => 35000,
        'categoria_lancamento_id' => automaticRegistrationCategory(CategoriaLancamento::SYSTEM_CATEGORY_INSCRICAO)->id,
        'registration_type' => null,
        'registration_id' => null,
    ]);
    automaticRegistrationSetCampistaStatus($multi, StatusInscricao::Cancelado);

    (new CancelRegistrationLaunchJob(Campista::class, $multi->id))->handle(app(AutomaticRegistrationLaunchService::class));

    expect($targetLaunch->fresh()->status)->toBe(StatusLacamento::Cancelado)
        ->and($paidLaunch->fresh()->status)->toBe(StatusLacamento::Pago)
        ->and($manualLaunch->fresh()->status)->toBe(StatusLacamento::Pendente)
        ->and($multiLaunch->fresh()->status)->toBe(StatusLacamento::Pendente);
});

it('reactivates the same automatic cancelled launch instead of creating a duplicate', function () {
    seedAutomaticRegistrationAmount(35000);
    CategoriaLancamento::ensureSystemDefaults();

    $campista = automaticRegistrationCampista('Reativação automática');
    $lancamento = automaticRegistrationAutomaticLaunch($campista, StatusLacamento::Cancelado);

    (new EnsureRegistrationLaunchJob(Campista::class, $campista->id))->handle(app(AutomaticRegistrationLaunchService::class));

    expect(Lancamento::query()->count())->toBe(1)
        ->and($lancamento->fresh()->status)->toBe(StatusLacamento::Pendente)
        ->and($lancamento->fresh()->origin_context)->toBe(Lancamento::ORIGIN_CONTEXT_OBSERVER);
});

it('supports dry run and dispatches reconciliation by default', function () {
    seedAutomaticRegistrationAmount(35000);
    CategoriaLancamento::ensureSystemDefaults();

    automaticRegistrationCampista('Dry Run Campista');

    $this->artisan('financeiro:reconcile-registration-launches --dry-run')
        ->assertSuccessful();

    expect(Lancamento::query()->count())->toBe(0);

    Queue::fake();

    $this->artisan('financeiro:reconcile-registration-launches --type=campista')
        ->assertSuccessful();

    Queue::assertPushed(ReconcileRegistrationLaunchesJob::class, fn (ReconcileRegistrationLaunchesJob $job): bool => $job->type === AutomaticRegistrationLaunchService::TYPE_CAMPISTA);
});

function seedAutomaticRegistrationAmount(int $amount, ?int $teamInternalAmount = null, ?int $teamExternalAmount = null): void
{
    foreach ([
        'valor_acampamento' => $amount,
        'valor_equipe_trabalho_interna' => $teamInternalAmount ?? $amount,
        'valor_equipe_trabalho_externa' => $teamExternalAmount ?? $amount,
    ] as $name => $value) {
        DB::table('settings')->updateOrInsert(
            [
                'group' => 'general',
                'name' => $name,
            ],
            [
                'payload' => json_encode($value),
            ],
        );
    }
}

function automaticRegistrationCampista(string $name, array $overrides = []): Campista
{
    return Campista::withoutEvents(fn (): Campista => Campista::factory()->create(array_replace([
        'nome' => $name,
        'status' => StatusInscricao::Pendente->value,
        'forma_pagamento' => FormaPagamento::NaoPago->value,
        'dia_pagamento' => null,
        'tribo_id' => null,
        'user_id' => null,
    ], $overrides)));
}

function automaticRegistrationEquipe(string $name, array $overrides = []): EquipeTrabalho
{
    return EquipeTrabalho::withoutEvents(fn (): EquipeTrabalho => EquipeTrabalho::factory()->create(array_replace([
        'nome' => $name,
        'status' => StatusInscricaoEquipeTrabalho::Pendente->value,
        'tribo_id' => null,
    ], $overrides)));
}

function automaticRegistrationSetCampistaStatus(Campista $campista, StatusInscricao $status): void
{
    Campista::withoutEvents(fn () => $campista->forceFill(['status' => $status])->save());
}

function automaticRegistrationCategory(string $systemKey): CategoriaLancamento
{
    return CategoriaLancamento::query()
        ->where('system_key', $systemKey)
        ->firstOrFail();
}

function automaticRegistrationAutomaticLaunch(Model $registration, StatusLacamento $status = StatusLacamento::Pendente): Lancamento
{
    return automaticRegistrationLaunch($registration, $status, automatic: true);
}

function automaticRegistrationManualLaunch(Model $registration): Lancamento
{
    return automaticRegistrationLaunch($registration, StatusLacamento::Pendente, automatic: false);
}

function automaticRegistrationLaunch(Model $registration, StatusLacamento $status, bool $automatic): Lancamento
{
    $category = automaticRegistrationCategory($registration instanceof EquipeTrabalho
        ? CategoriaLancamento::SYSTEM_CATEGORY_CONTRIBUICAO_EQUIPE_TRABALHO
        : CategoriaLancamento::SYSTEM_CATEGORY_INSCRICAO);

    $lancamento = Lancamento::factory()->create([
        'nome' => 'Lançamento '.$registration->getAttribute('nome'),
        'descricao' => null,
        'comprador' => null,
        'data' => '2026-06-11',
        'valor' => 35000,
        'tipo' => TipoLacamento::Receita->value,
        'status' => $status->value,
        'forma_pagamento' => FormaPagamento::NaoPago->value,
        'comprovante' => [],
        'batch_code' => null,
        'user_id' => null,
        'origin' => $automatic ? Lancamento::ORIGIN_AUTO_REGISTRATION : null,
        'origin_context' => $automatic ? Lancamento::ORIGIN_CONTEXT_OBSERVER : null,
    ]);

    $lancamento->items()->create([
        'nome' => (string) $registration->getAttribute('nome'),
        'descricao' => null,
        'valor' => 35000,
        'categoria_lancamento_id' => $category->id,
        'registration_type' => $registration::class,
        'registration_id' => $registration->getKey(),
    ]);

    return $lancamento;
}
