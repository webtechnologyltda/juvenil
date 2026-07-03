<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Resources\LancamentoResource\LancamentoExport;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function lancamentoExportCategory(string $name, TipoLacamento $type): CategoriaLancamento
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

it('exports updated financial entry columns', function () {
    $columnNames = collect(LancamentoExport::getExportColumns())
        ->map(fn ($column): string => $column->getName())
        ->all();

    expect($columnNames)->toContain(
        'items_summary',
        'item_values_summary',
        'item_descriptions_summary',
        'registration_payments_summary',
        'comprovantes_summary',
        'comprovante_observacoes_summary',
        'origin',
        'origin_context',
        'created_at',
        'updated_at',
    );
});

it('exports launch rich text and item summaries as plain text', function () {
    $category = lancamentoExportCategory('Inscrição', TipoLacamento::Receita);
    $campista = Campista::factory()->create([
        'nome' => 'João Exportação',
        'status' => StatusInscricao::Pago->value,
    ]);
    $lancamento = Lancamento::query()->create([
        'nome' => 'Lançamento exportável',
        'descricao' => '<p>Pagamento <strong>confirmado</strong></p><ul><li>Sem HTML</li></ul>',
        'comprador' => null,
        'data' => '2026-07-03',
        'valor' => 25000,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'comprovante' => [
            [
                'type' => 'anexar_comprovante',
                'data' => [
                    'url' => ['comprovantes/pix-exportacao.pdf'],
                    'observacao' => '<p>Recebido <strong>via Pix</strong></p>',
                ],
            ],
        ],
        'batch_code' => 'LOTE-EXPORT',
        'origin' => Lancamento::ORIGIN_AUTO_REGISTRATION,
        'origin_context' => Lancamento::ORIGIN_CONTEXT_DAILY_RECONCILIATION,
        'user_id' => null,
    ]);

    $lancamento->items()->create([
        'nome' => 'Parcela inscrição',
        'descricao' => '<p>Item <em>principal</em></p>',
        'valor' => 25000,
        'categoria_lancamento_id' => $category->id,
        'registration_type' => Campista::class,
        'registration_id' => $campista->id,
    ]);

    $lancamento->load(['items.categoria', 'items.registration']);

    expect(LancamentoExport::plainText($lancamento->descricao))->toBe("Pagamento confirmado\nSem HTML")
        ->and(LancamentoExport::itemsSummary($lancamento))->toBe('Parcela inscrição (Inscrição)')
        ->and(LancamentoExport::itemValuesSummary($lancamento))->toBe('Parcela inscrição: R$ 250,00')
        ->and(LancamentoExport::itemDescriptionsSummary($lancamento))->toBe('Parcela inscrição: Item principal')
        ->and(LancamentoExport::registrationsSummary($lancamento))->toBe('Campista #'.$campista->id.' - João Exportação (R$ 250,00)')
        ->and(LancamentoExport::comprovantesSummary($lancamento))->toBe('pix-exportacao.pdf')
        ->and(LancamentoExport::comprovanteObservationsSummary($lancamento))->toBe('Recebido via Pix');
});
