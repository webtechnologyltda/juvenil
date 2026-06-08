<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Resources\LancamentoResource;
use App\Filament\Resources\LancamentoResource\Pages\BatchLancamentos;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Models\User;
use App\Support\Financeiro\LancamentoBatchCreator;
use Carbon\Carbon;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('registers a dedicated batch launch page and list action', function () {
    $pages = LancamentoResource::getPages();
    $listPage = file_get_contents(app_path('Filament/Resources/LancamentoResource/Pages/ListLancamentos.php'));
    $batchPage = file_get_contents(app_path('Filament/Resources/LancamentoResource/Pages/BatchLancamentos.php'));
    $view = file_get_contents(resource_path('views/filament/resources/lancamento-resource/pages/batch-lancamentos.blade.php'));

    expect($pages)
        ->toHaveKey('batch')
        ->and($listPage)
        ->toContain('batch')
        ->toContain('Lançamento em lote')
        ->and($batchPage)
        ->toContain("ModalTableSelect::make('registration_ids')")
        ->toContain('LancamentoBatchCampistasTable::class')
        ->toContain('LancamentoBatchEquipeTrabalhoTable::class')
        ->not->toContain('->getSearchResultsUsing(fn (Get $get, string $search): array => app(LancamentoBatchCreator::class)')
        ->toContain("Repeater::make('manual_items')")
        ->toContain("Action::make('createBatch')")
        ->and(file_get_contents(app_path('Filament/Resources/LancamentoResource/Tables/LancamentoBatchCampistasTable.php')))
        ->toContain('CampistaTable::getListTableColumns()')
        ->toContain('LancamentoBatchCreator::class')
        ->and($view)
        ->toContain('{{ $this->form }}');
});

it('shows a visual warning when the configured camp amount is zero on batch registration mode', function () {
    $this->seed(ShieldSeeder::class);
    seedLancamentoBatchSettings(0);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    Livewire::test(BatchLancamentos::class)
        ->assertSee('Valor do acampamento não configurado')
        ->assertSee('O campo de inscrições pode ficar sem opções enquanto o valor estiver zerado nas configurações.');
});

it('keeps registration item values in cents when propagating a masked default amount', function () {
    $this->seed(ShieldSeeder::class);
    seedLancamentoBatchSettings(35000);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $campista = lancamentoBatchCampista('Ana Souza 001');

    Livewire::test(BatchLancamentos::class)
        ->set('data.default_value', '350,00')
        ->set('data.registration_ids', [$campista->id])
        ->assertSet('data.registration_items.0.valor', 35000);
});

it('creates one pending launch per selected campista registration using the default inscription category', function () {
    Carbon::setTestNow('2026-06-08 09:00:00');
    seedLancamentoBatchSettings(35000);
    CategoriaLancamento::ensureSystemDefaults();

    $joao = lancamentoBatchCampista('João Lote');
    $maria = lancamentoBatchCampista('Maria Lote');

    $created = app(LancamentoBatchCreator::class)->create([
        'mode' => 'registrations',
        'registration_type' => Campista::class,
        'registration_ids' => [$joao->id, $maria->id],
        'data' => '2026-07-01',
        'descricao' => 'Lote de inscrições',
    ]);

    $category = lancamentoBatchSystemCategory(CategoriaLancamento::SYSTEM_CATEGORY_INSCRICAO);

    expect($created)
        ->toHaveCount(2)
        ->and(Lancamento::query()->pluck('batch_code')->unique()->values()->all())
        ->toBe(['LOTE-20260608-001'])
        ->and(Lancamento::query()->pluck('status')->unique()->values()->all())
        ->toBe([StatusLacamento::Pendente])
        ->and(Lancamento::query()->pluck('forma_pagamento')->unique()->values()->all())
        ->toBe([FormaPagamento::NaoPago])
        ->and(Lancamento::query()->pluck('valor')->all())
        ->toBe([35000, 35000])
        ->and(Lancamento::query()->with('items')->get()->flatMap->items->pluck('categoria_lancamento_id')->all())
        ->toBe([$category->id, $category->id])
        ->and($joao->fresh()->status)->toBe(StatusInscricao::Pendente)
        ->and($maria->fresh()->status)->toBe(StatusInscricao::Pendente);

    Carbon::setTestNow();
});

it('stores Brazilian masked registration item amounts as cents when creating a batch', function () {
    seedLancamentoBatchSettings(35000);
    CategoriaLancamento::ensureSystemDefaults();

    $campista = lancamentoBatchCampista('Ana Souza 001');

    app(LancamentoBatchCreator::class)->create([
        'mode' => 'registrations',
        'registration_type' => Campista::class,
        'registration_ids' => [$campista->id],
        'data' => '2026-07-01',
        'registration_items' => [
            [
                'registration_id' => $campista->id,
                'nome' => 'Ana Souza 001',
                'valor' => '350,00',
                'descricao' => null,
            ],
        ],
    ]);

    expect(Lancamento::query()->firstOrFail())
        ->valor->toBe(35000)
        ->items()->firstOrFail()->valor->toBe(35000);
});

it('creates team work contribution batches with the team contribution category', function () {
    Carbon::setTestNow('2026-06-08 09:00:00');
    seedLancamentoBatchSettings(12000);
    CategoriaLancamento::ensureSystemDefaults();

    $member = EquipeTrabalho::factory()->create([
        'nome' => 'Equipe Lote',
        'status' => StatusInscricaoEquipeTrabalho::Pendente->value,
    ]);

    app(LancamentoBatchCreator::class)->create([
        'mode' => 'registrations',
        'registration_type' => EquipeTrabalho::class,
        'registration_ids' => [$member->id],
        'data' => '2026-07-01',
    ]);

    $category = lancamentoBatchSystemCategory(CategoriaLancamento::SYSTEM_CATEGORY_CONTRIBUICAO_EQUIPE_TRABALHO);

    expect(Lancamento::query()->firstOrFail()->items()->firstOrFail())
        ->categoria_lancamento_id->toBe($category->id)
        ->registration_type->toBe(EquipeTrabalho::class)
        ->registration_id->toBe($member->id);

    Carbon::setTestNow();
});

it('excludes cancelled and already linked registrations from batch options and validation', function () {
    seedLancamentoBatchSettings(35000);
    CategoriaLancamento::ensureSystemDefaults();

    $available = lancamentoBatchCampista('Disponível Lote');
    $cancelled = lancamentoBatchCampista('Cancelado Lote', [
        'status' => StatusInscricao::Cancelado->value,
    ]);
    $linked = lancamentoBatchCampista('Vinculado Lote');
    $category = lancamentoBatchSystemCategory(CategoriaLancamento::SYSTEM_CATEGORY_INSCRICAO);

    $launch = Lancamento::factory()->create([
        'status' => StatusLacamento::Pendente->value,
        'tipo' => TipoLacamento::Receita->value,
        'forma_pagamento' => FormaPagamento::NaoPago->value,
        'valor' => 35000,
        'user_id' => null,
    ]);
    $launch->items()->create([
        'nome' => $linked->nome,
        'valor' => 35000,
        'categoria_lancamento_id' => $category->id,
        'registration_type' => $linked::class,
        'registration_id' => $linked->id,
    ]);

    $options = app(LancamentoBatchCreator::class)->registrationOptions(Campista::class);

    expect($options)
        ->toHaveKey($available->id)
        ->not->toHaveKey($cancelled->id)
        ->not->toHaveKey($linked->id);

    expect(fn () => app(LancamentoBatchCreator::class)->create([
        'mode' => 'registrations',
        'registration_type' => Campista::class,
        'registration_ids' => [$available->id, $cancelled->id],
        'data' => '2026-07-01',
    ]))->toThrow(ValidationException::class);
});

it('creates one pending manual launch per free item with editable categories', function () {
    Carbon::setTestNow('2026-06-08 09:00:00');

    $donation = CategoriaLancamento::query()->create([
        'nome' => 'Doação avulsa',
        'tipo' => TipoLacamento::Doacao,
        'cor' => '#0ea5e9',
        'icone' => 'iconoir-donate',
        'ativo' => true,
    ]);

    $created = app(LancamentoBatchCreator::class)->create([
        'mode' => 'manual',
        'tipo' => TipoLacamento::Doacao->value,
        'data' => '2026-07-02',
        'manual_items' => [
            [
                'nome' => 'Doação comunidade',
                'valor' => 10000,
                'categoria_lancamento_id' => $donation->id,
                'descricao' => 'Entrada avulsa',
            ],
            [
                'nome' => 'Doação visitante',
                'valor' => 5000,
                'categoria_lancamento_id' => $donation->id,
                'descricao' => null,
            ],
        ],
    ]);

    expect($created)
        ->toHaveCount(2)
        ->and(Lancamento::query()->pluck('nome')->all())
        ->toBe(['Doação comunidade', 'Doação visitante'])
        ->and(Lancamento::query()->pluck('batch_code')->unique()->values()->all())
        ->toBe(['LOTE-20260608-001'])
        ->and(Lancamento::query()->pluck('status')->unique()->values()->all())
        ->toBe([StatusLacamento::Pendente])
        ->and(Lancamento::query()->with('items')->get()->flatMap->items->pluck('categoria_lancamento_id')->unique()->values()->all())
        ->toBe([$donation->id]);

    Carbon::setTestNow();
});

it('increments the daily batch code and does not keep partial launches when validation fails', function () {
    Carbon::setTestNow('2026-06-08 09:00:00');

    Lancamento::factory()->create([
        'batch_code' => 'LOTE-20260608-001',
        'user_id' => null,
    ]);

    $expense = CategoriaLancamento::query()->create([
        'nome' => 'Despesa avulsa',
        'tipo' => TipoLacamento::Despesa,
        'cor' => '#ef4444',
        'icone' => 'heroicon-o-shopping-cart',
        'ativo' => true,
    ]);

    expect(app(LancamentoBatchCreator::class)->nextBatchCode())->toBe('LOTE-20260608-002');

    try {
        app(LancamentoBatchCreator::class)->create([
            'mode' => 'manual',
            'tipo' => TipoLacamento::Receita->value,
            'data' => '2026-07-02',
            'manual_items' => [
                [
                    'nome' => 'Categoria incompatível',
                    'valor' => 10000,
                    'categoria_lancamento_id' => $expense->id,
                ],
            ],
        ]);
    } catch (ValidationException) {
        //
    }

    expect(Lancamento::query()->count())->toBe(1);

    Carbon::setTestNow();
});

it('creates registration batches from the Filament page', function () {
    $this->seed(ShieldSeeder::class);
    seedLancamentoBatchSettings(35000);
    CategoriaLancamento::ensureSystemDefaults();

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $campista = lancamentoBatchCampista('Campista Pela Tela');

    Livewire::test(BatchLancamentos::class)
        ->fillForm([
            'mode' => 'registrations',
            'registration_type' => Campista::class,
            'registration_ids' => [$campista->id],
            'data' => '2026-07-01',
            'descricao' => 'Criado pela página de lote',
        ])
        ->call('createBatch')
        ->assertHasNoFormErrors();

    expect(Lancamento::query()->where('batch_code', 'like', 'LOTE-%')->count())
        ->toBe(1)
        ->and(Lancamento::query()->firstOrFail()->items()->firstOrFail()->registration_id)
        ->toBe($campista->id);
});

function seedLancamentoBatchSettings(int $amount): void
{
    DB::table('settings')->updateOrInsert(
        [
            'group' => 'general',
            'name' => 'valor_acampamento',
        ],
        [
            'payload' => json_encode($amount),
        ],
    );
}

function lancamentoBatchCampista(string $name, array $overrides = []): Campista
{
    return Campista::factory()->create(array_replace([
        'nome' => $name,
        'status' => StatusInscricao::Pendente->value,
        'forma_pagamento' => FormaPagamento::NaoPago->value,
        'dia_pagamento' => null,
        'tribo_id' => null,
        'user_id' => null,
    ], $overrides));
}

function lancamentoBatchSystemCategory(string $systemKey): CategoriaLancamento
{
    return CategoriaLancamento::query()
        ->where('system_key', $systemKey)
        ->firstOrFail();
}
