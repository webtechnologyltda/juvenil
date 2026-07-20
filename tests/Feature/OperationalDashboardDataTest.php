<?php

use App\Enums\StatusInscricao;
use App\Models\Campista;
use App\Models\Tribo;
use App\Support\Dashboard\OperationalDashboardData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeOperationalDashboardCampista(array $attributes = [], array $formData = []): Campista
{
    return Campista::query()->create(array_merge([
        'nome' => fake()->name(),
        'avatar_url' => 'foto-formulario/test.png',
        'status' => StatusInscricao::Pendente->value,
        'presenca' => false,
        'form_data' => array_merge([
            'data_nacimento' => '15/02/2000',
            'sexo' => 'M',
            'telefone_campista' => '(11) 9 9999-9999',
            'telefone_reponsavel_1' => '(11) 9 8888-8888',
            'telefone_reponsavel_nome_1' => 'Responsavel',
            'paroquia' => 0,
            'comunidade' => 1,
            'tamanho_camiseta' => 'M',
            'tamanho_camiseta_outro' => null,
            'toma_remedio' => false,
            'remedio' => null,
            'tem_recomendacao' => false,
            'recomendacao' => null,
        ], $formData),
    ], $attributes));
}

it('calculates the operational pipeline excluding cancelled records by default', function () {
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pendente->value]);
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value, 'presenca' => false]);
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value, 'presenca' => true]);
    makeOperationalDashboardCampista(['status' => StatusInscricao::Cancelado->value]);

    $data = app(OperationalDashboardData::class)->forFilters([]);

    expect($data->pipeline())->toMatchArray([
        'valid' => 3,
        'pending_payment' => 1,
        'paid' => 2,
        'awaiting_check_in' => 1,
        'present' => 1,
        'cancelled' => 1,
    ]);
});

it('defers operational queries and uses grouped queries for dashboard metrics', function () {
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value]);
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $data = app(OperationalDashboardData::class)->forFilters([]);

    expect(DB::getQueryLog())->toHaveCount(0);

    $data->pipeline();

    expect(DB::getQueryLog())->toHaveCount(1)
        ->and(strtolower(DB::getQueryLog()[0]['query']))
        ->toContain('count(*) as aggregate')
        ->not->toContain('select *');

    $data->tribes();
    $data->tribeColors();

    expect(DB::getQueryLog())->toHaveCount(2);

    DB::disableQueryLog();
});

it('applies status tribe parish community and presence filters to operational metrics', function () {
    $blue = Tribo::query()->create(['cor' => 'Azul', 'cor_hex' => '#123abc']);
    $red = Tribo::query()->create(['cor' => 'Vermelha']);

    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value, 'presenca' => false, 'tribo_id' => $blue->id], ['paroquia' => 0, 'comunidade' => 2]);
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value, 'presenca' => true, 'tribo_id' => $blue->id], ['paroquia' => 0, 'comunidade' => 2]);
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value, 'presenca' => false, 'tribo_id' => $red->id], ['paroquia' => 1, 'comunidade' => 3]);
    makeOperationalDashboardCampista(['status' => StatusInscricao::Cancelado->value, 'presenca' => false, 'tribo_id' => $blue->id], ['paroquia' => 0, 'comunidade' => 2]);

    $data = app(OperationalDashboardData::class)->forFilters([
        'status' => [StatusInscricao::Pago->value],
        'tribo_id' => [$blue->id],
        'paroquia' => '0',
        'comunidade' => '2',
        'presenca' => '0',
    ]);

    expect($data->pipeline())->toMatchArray([
        'valid' => 1,
        'paid' => 1,
        'awaiting_check_in' => 1,
        'present' => 0,
        'cancelled' => 1,
    ])
        ->and($data->tribes())->toBe(['Azul' => 1])
        ->and($data->communities())->toBe([
            'São Domingos e N. Sra. do Carmo / São Paulo' => 1,
        ]);
});

it('filters operational metrics by multiple selected communities for fixed parishes', function () {
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value], ['paroquia' => 0, 'comunidade' => 1]);
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value], ['paroquia' => 0, 'comunidade' => 2]);
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value], ['paroquia' => 0, 'comunidade' => 4]);
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value], ['paroquia' => 1, 'comunidade' => 1]);

    $data = app(OperationalDashboardData::class)->forFilters([
        'paroquia' => '0',
        'comunidade' => ['1', '4'],
    ]);

    expect($data->pipeline())->toMatchArray([
        'valid' => 2,
        'paid' => 2,
    ])
        ->and($data->communities())->toHaveKey('São Domingos e N. Sra. do Carmo / Nossa Senhora das Graças', 1)
        ->and($data->communities())->toHaveKey('São Domingos e N. Sra. do Carmo / Imaculado Coração de Maria', 1)
        ->and($data->communities())->not->toHaveKey('São Domingos e N. Sra. do Carmo / São Paulo')
        ->and($data->communities())->not->toHaveKey('Santa Luzia / Santa Teresinha');
});

it('filters operational metrics by community text when another parish is selected', function () {
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value], [
        'paroquia' => 2,
        'comunidade' => 'Comunidade São Pedro - Navegantes',
    ]);
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value], [
        'paroquia' => 2,
        'comunidade' => 'Capela Santa Rita',
    ]);
    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value], [
        'paroquia' => 0,
        'comunidade' => 2,
    ]);

    $data = app(OperationalDashboardData::class)->forFilters([
        'paroquia' => '2',
        'comunidade_texto' => 'Pedro',
    ]);

    expect($data->pipeline())->toMatchArray([
        'valid' => 1,
        'paid' => 1,
    ])
        ->and($data->communities())->toBe([
            'Outra paróquia / Comunidade São Pedro - Navegantes' => 1,
        ]);
});

it('builds tribe shirt community age and sex distributions with incomplete form data tolerance', function () {
    $blue = Tribo::query()->create(['cor' => 'Azul', 'cor_hex' => '#123abc']);

    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value, 'tribo_id' => $blue->id], [
        'sexo' => 'M',
        'data_nacimento' => '15/02/2000',
        'paroquia' => 0,
        'comunidade' => 4,
        'tamanho_camiseta' => 'O',
        'tamanho_camiseta_outro' => 'XGG',
    ]);

    makeOperationalDashboardCampista(['status' => StatusInscricao::Pago->value], [
        'sexo' => 'F',
        'data_nacimento' => '20/07/1985',
        'paroquia' => 1,
        'comunidade' => 7,
        'tamanho_camiseta' => 'M',
    ]);

    Campista::query()->create([
        'nome' => 'Registro incompleto',
        'status' => StatusInscricao::Pago->value,
        'presenca' => false,
        'form_data' => [],
    ]);

    $data = app(OperationalDashboardData::class)->forFilters([]);

    expect($data->tribes())->toHaveKey('Azul', 1)
        ->and($data->tribes())->toHaveKey('Sem tribo', 2)
        ->and($data->tribeColors())->toHaveKey('Azul', '#123abc')
        ->and($data->tribeColors())->toHaveKey('Sem tribo', '#94a3b8')
        ->and($data->shirts())->toHaveKey('Outros: XGG', 1)
        ->and($data->shirts())->toHaveKey('M', 1)
        ->and($data->shirts())->toHaveKey('Sem tamanho', 1)
        ->and($data->communities())->toHaveKey('São Domingos e N. Sra. do Carmo / Imaculado Coração de Maria', 1)
        ->and($data->communities())->toHaveKey('Santa Luzia / Nossa Senhora Aparecida', 1)
        ->and($data->communities())->toHaveKey('Sem comunidade', 1)
        ->and($data->ages())->toHaveKey('Ate 29', 1)
        ->and($data->ages())->toHaveKey('40-44', 1)
        ->and($data->ages())->toHaveKey('Sem data', 1)
        ->and($data->sexes())->toHaveKey('Masculino', 1)
        ->and($data->sexes())->toHaveKey('Feminino', 1)
        ->and($data->sexes())->toHaveKey('Sem sexo', 1);
});

it('summarizes health indicators and returns only sensitive health rows', function () {
    makeOperationalDashboardCampista(['nome' => 'Com remedio', 'status' => StatusInscricao::Pago->value], [
        'toma_remedio' => true,
        'remedio' => 'Medicamento A',
        'tem_recomendacao' => false,
    ]);

    makeOperationalDashboardCampista(['nome' => 'Com recomendacao', 'status' => StatusInscricao::Pago->value], [
        'toma_remedio' => false,
        'tem_recomendacao' => '1',
        'recomendacao' => 'Cuidado especial',
    ]);

    makeOperationalDashboardCampista(['nome' => 'Com ambos', 'status' => StatusInscricao::Pago->value], [
        'toma_remedio' => 1,
        'remedio' => 'Medicamento B',
        'tem_recomendacao' => true,
        'recomendacao' => 'Observacao',
    ]);

    makeOperationalDashboardCampista(['nome' => 'Sem saude', 'status' => StatusInscricao::Pago->value]);
    makeOperationalDashboardCampista(['nome' => 'Cancelado sensivel', 'status' => StatusInscricao::Cancelado->value], [
        'toma_remedio' => true,
        'tem_recomendacao' => true,
    ]);

    $data = app(OperationalDashboardData::class)->forFilters([]);

    expect($data->healthSummary())->toMatchArray([
        'medicine' => 2,
        'recommendation' => 2,
        'both' => 1,
    ])
        ->and($data->sensitiveHealthRecords())->toHaveCount(3)
        ->and($data->sensitiveHealthRecords()->pluck('nome')->all())->toBe([
            'Com remedio',
            'Com recomendacao',
            'Com ambos',
        ]);
});

it('detects pending operational issues without treating tribe as campista data debt', function () {
    makeOperationalDashboardCampista([], [
        'telefone_campista' => '',
        'telefone_reponsavel_1' => '',
        'telefone_reponsavel_nome_1' => '',
        'paroquia' => null,
        'comunidade' => null,
        'tamanho_camiseta' => '',
    ]);

    Campista::query()->create([
        'nome' => 'Sem foto',
        'status' => StatusInscricao::Pago->value,
        'presenca' => false,
        'avatar_url' => null,
        'form_data' => [
            'telefone_campista' => '(11) 9 9999-9999',
            'telefone_reponsavel_1' => '(11) 9 8888-8888',
            'telefone_reponsavel_nome_1' => 'Responsavel',
            'paroquia' => 0,
            'comunidade' => 1,
            'tamanho_camiseta' => 'G',
        ],
    ]);

    $pending = app(OperationalDashboardData::class)->forFilters([])->pendingTasks();

    expect($pending)->toHaveCount(2)
        ->and($pending->first()['issues'])->toContain('Sem telefone do campista')
        ->toContain('Sem telefone do responsavel')
        ->toContain('Sem nome do responsavel')
        ->toContain('Sem paroquia/comunidade')
        ->toContain('Sem tamanho de camiseta')
        ->not->toContain('Sem tribo')
        ->and($pending->last()['issues'])->toBe(['Sem foto']);
});
