<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('moves financial classification from lancamentos to lancamento items', function () {
    expect(Schema::hasTable('lancamento_items'))->toBeTrue()
        ->and(Schema::hasColumn('lancamento_items', 'categoria_lancamento_id'))->toBeTrue()
        ->and(Schema::hasColumn('lancamento_items', 'registration_type'))->toBeTrue()
        ->and(Schema::hasColumn('lancamento_items', 'registration_id'))->toBeTrue()
        ->and(Schema::hasColumn('lancamentos', 'batch_code'))->toBeTrue()
        ->and(Schema::hasColumn('lancamentos', 'categoria_lancamento_id'))->toBeFalse()
        ->and(Schema::hasTable('financial_entry_registrations'))->toBeFalse();
});

it('syncs launch totals from items and marks paid registrations from paid item values', function () {
    seedLancamentoItemSettings(25000);
    CategoriaLancamento::ensureSystemDefaults();

    $category = CategoriaLancamento::query()
        ->where('system_key', CategoriaLancamento::SYSTEM_CATEGORY_INSCRICAO)
        ->firstOrFail();

    $campista = Campista::factory()->create([
        'nome' => 'João Item',
        'status' => StatusInscricao::Pendente->value,
        'forma_pagamento' => FormaPagamento::NaoPago->value,
        'dia_pagamento' => null,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    $lancamento = lancamentoItemEntry([
        'valor' => 0,
        'status' => StatusLacamento::Pago->value,
    ]);

    app(RegistrationPaymentAllocator::class)->syncItems($lancamento, [
        [
            'nome' => 'João Item',
            'valor' => 25000,
            'categoria_lancamento_id' => $category->id,
            'registration_type' => Campista::class,
            'registration_id' => $campista->id,
        ],
    ]);

    expect($lancamento->fresh()->valor)->toBe(25000)
        ->and($lancamento->fresh()->items)->toHaveCount(1)
        ->and($lancamento->fresh()->items->first()->categoria_lancamento_id)->toBe($category->id)
        ->and(app(RegistrationPaymentAllocator::class)->paidAmountFor($campista->fresh()))->toBe(25000)
        ->and(app(RegistrationPaymentAllocator::class)->remainingAmountFor($campista->fresh()))->toBe(0)
        ->and($campista->fresh()->status)->toBe(StatusInscricao::Pago)
        ->and($campista->fresh()->forma_pagamento)->toBe(FormaPagamento::Pix)
        ->and($campista->fresh()->dia_pagamento?->format('Y-m-d'))->toBe('2026-07-01');
});

it('validates item category compatibility with launch type', function () {
    $expenseCategory = CategoriaLancamento::query()->create([
        'nome' => 'Mercado',
        'tipo' => TipoLacamento::Despesa,
        'cor' => '#ef4444',
        'icone' => 'heroicon-o-shopping-cart',
        'ativo' => true,
    ]);

    $lancamento = lancamentoItemEntry([
        'tipo' => TipoLacamento::Receita->value,
        'valor' => 0,
    ]);

    expect(fn () => app(RegistrationPaymentAllocator::class)->syncItems($lancamento, [
        [
            'nome' => 'Item incompatível',
            'valor' => 1000,
            'categoria_lancamento_id' => $expenseCategory->id,
        ],
    ]))->toThrow(ValidationException::class);
});

function seedLancamentoItemSettings(int $amount): void
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

function lancamentoItemEntry(array $overrides = []): Lancamento
{
    return Lancamento::factory()->create(array_replace([
        'nome' => 'Lançamento por item',
        'descricao' => 'Pagamento por item',
        'comprador' => null,
        'data' => '2026-07-01',
        'valor' => 0,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pendente->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'comprovante' => [],
        'user_id' => null,
    ], $overrides));
}
