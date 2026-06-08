<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use App\Support\Dashboard\FinancialDashboardData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeFinancialDashboardCategory(string $name, TipoLacamento $type): CategoriaLancamento
{
    return CategoriaLancamento::query()->firstOrCreate(
        [
            'nome' => $name,
            'tipo' => $type->value,
        ],
        [
            'cor' => '#f46b12',
            'icone' => 'heroicon-o-banknotes',
            'ativo' => true,
        ],
    );
}

function makeFinancialDashboardEntry(array $attributes = []): Lancamento
{
    $category = $attributes['categoria_lancamento_id'] ?? null;
    unset($attributes['categoria_lancamento_id']);

    $lancamento = Lancamento::query()->create(array_merge([
        'nome' => 'Lançamento financeiro',
        'descricao' => null,
        'comprador' => null,
        'data' => '2026-06-07 10:00:00',
        'valor' => 10000,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'comprovante' => [],
        'user_id' => null,
    ], $attributes));

    if ($category !== null) {
        $lancamento->items()->create([
            'nome' => $lancamento->nome,
            'descricao' => $lancamento->descricao,
            'valor' => abs((int) $lancamento->valor),
            'categoria_lancamento_id' => $category,
        ]);
    }

    return $lancamento;
}

it('summarizes paid financial entries as revenue donations expenses and balance by default', function () {
    makeFinancialDashboardEntry([
        'nome' => 'Inscrição paga',
        'valor' => 25000,
        'tipo' => TipoLacamento::Receita->value,
    ]);
    makeFinancialDashboardEntry([
        'nome' => 'Doação paga',
        'valor' => 7500,
        'tipo' => TipoLacamento::Doacao->value,
    ]);
    makeFinancialDashboardEntry([
        'nome' => 'Compra paga',
        'valor' => 5000,
        'tipo' => TipoLacamento::Despesa->value,
    ]);
    makeFinancialDashboardEntry([
        'nome' => 'Receita pendente',
        'valor' => 9900,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pendente->value,
    ]);

    $summary = app(FinancialDashboardData::class)->forFilters([])->summary();

    expect($summary)->toMatchArray([
        'entries' => 3,
        'revenue' => 25000,
        'donations' => 7500,
        'expenses' => 5000,
        'balance' => 27500,
    ]);
});

it('applies financial dashboard filters to totals charts and recent entries', function () {
    $inscricao = makeFinancialDashboardCategory('Inscrição', TipoLacamento::Receita);
    $alimentacao = makeFinancialDashboardCategory('Alimentação', TipoLacamento::Despesa);

    makeFinancialDashboardEntry([
        'nome' => 'PIX filtrado',
        'data' => '2026-06-07 09:00:00',
        'valor' => 25000,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'categoria_lancamento_id' => $inscricao->id,
    ]);
    makeFinancialDashboardEntry([
        'nome' => 'Despesa filtrada',
        'data' => '2026-06-07 12:00:00',
        'valor' => 4000,
        'tipo' => TipoLacamento::Despesa->value,
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'categoria_lancamento_id' => $alimentacao->id,
    ]);
    makeFinancialDashboardEntry([
        'nome' => 'Dinheiro fora',
        'data' => '2026-06-07 14:00:00',
        'valor' => 10000,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Dinheiro->value,
        'categoria_lancamento_id' => $inscricao->id,
    ]);
    makeFinancialDashboardEntry([
        'nome' => 'Data fora',
        'data' => '2026-06-06 10:00:00',
        'valor' => 30000,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'categoria_lancamento_id' => $inscricao->id,
    ]);

    $data = app(FinancialDashboardData::class)->forFilters([
        'data_inicio' => '2026-06-07',
        'data_fim' => '2026-06-07',
        'status' => [StatusLacamento::Pago->value],
        'tipo' => [TipoLacamento::Receita->value, TipoLacamento::Despesa->value],
        'forma_pagamento' => [FormaPagamento::Pix->value],
    ]);

    expect($data->summary())->toMatchArray([
        'entries' => 2,
        'revenue' => 25000,
        'expenses' => 4000,
        'balance' => 21000,
    ])
        ->and($data->dailyFlow())->toBe([
            '07/06' => [
                'revenue' => 25000,
                'donations' => 0,
                'expenses' => 4000,
                'balance' => 21000,
            ],
        ])
        ->and($data->categoryTotals())->toBe([
            'Inscrição' => 25000,
            'Alimentação' => 4000,
        ])
        ->and($data->recentEntries()->pluck('nome')->all())->toBe([
            'Despesa filtrada',
            'PIX filtrado',
        ]);
});
