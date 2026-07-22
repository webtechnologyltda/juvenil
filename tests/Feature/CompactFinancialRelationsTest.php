<?php

use AnourValar\EloquentSerialize\Service;
use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Resources\LancamentoResource;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use App\Models\User;
use App\Support\Reports\RegistrationPaymentReportData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('serializes compact financial relations for queued exports', function () {
    [$lancamento] = financialRelationRecords();
    $method = new ReflectionMethod(LancamentoResource::class, 'withCompactItemRelations');
    $query = $method->invoke(null, Lancamento::query()->whereKey($lancamento), true);
    $serializer = app(Service::class);

    $serializedQuery = $serializer->serialize($query);
    $restoredItem = $serializer->unserialize($serializedQuery)
        ->firstOrFail()
        ->items
        ->firstOrFail();

    expect($serializedQuery)->toBeString()->not->toBeEmpty()
        ->and(array_keys($restoredItem->registration->getAttributes()))->toEqualCanonicalizing([
            'id',
            'nome',
        ]);
});

it('loads only the columns used by financial entry badges', function () {
    [$lancamento] = financialRelationRecords();

    $method = new ReflectionMethod(LancamentoResource::class, 'withCompactItemRelations');
    $query = $method->invoke(null, Lancamento::query()->whereKey($lancamento));

    expect($query)->toBeInstanceOf(Builder::class);

    $loadedLancamento = $query->firstOrFail();
    $item = $loadedLancamento->items->firstOrFail();

    expect(array_keys($item->getAttributes()))->toEqualCanonicalizing([
        'id',
        'lancamento_id',
        'nome',
        'valor',
        'categoria_lancamento_id',
        'registration_type',
        'registration_id',
    ])->and(array_keys($item->categoria->getAttributes()))->toEqualCanonicalizing([
        'id',
        'nome',
        'cor',
        'icone',
    ])->and(array_keys($item->registration->getAttributes()))->toEqualCanonicalizing([
        'id',
        'nome',
    ]);
});

it('keeps report output while selecting only registration identity columns', function () {
    [, $campista] = financialRelationRecords();
    $queries = [];

    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = Str::of($query->sql)
            ->replace(['`', '"'], '')
            ->squish()
            ->toString();
    });

    $rows = app(RegistrationPaymentReportData::class)->rows([]);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['registration_name'])->toBe($campista->nome)
        ->and(collect($queries)->contains(
            fn (string $query): bool => str_starts_with($query, 'select id, nome from campistas'),
        ))->toBeTrue()
        ->and(collect($queries)->contains(
            fn (string $query): bool => str_contains($query, 'form_data') || str_contains($query, 'avatar_url'),
        ))->toBeFalse();
});

/**
 * @return array{Lancamento, Campista}
 */
function financialRelationRecords(): array
{
    $user = User::factory()->create();
    $campista = Campista::factory()->create(['nome' => 'Campista Compacto']);
    $category = CategoriaLancamento::factory()->create([
        'nome' => 'Inscrição compacta',
        'tipo' => TipoLacamento::Receita,
    ]);
    $lancamento = Lancamento::factory()->create([
        'user_id' => $user->id,
        'nome' => 'Lançamento compacto',
        'tipo' => TipoLacamento::Receita,
        'status' => StatusLacamento::Pago,
        'forma_pagamento' => FormaPagamento::Pix,
        'data' => '2026-07-19',
    ]);

    $lancamento->items()->create([
        'nome' => 'Inscrição de campista',
        'valor' => 25000,
        'categoria_lancamento_id' => $category->id,
        'registration_type' => Campista::class,
        'registration_id' => $campista->id,
    ]);

    return [$lancamento, $campista];
}
