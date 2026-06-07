<?php

use App\Enums\StatusInscricao;
use App\Filament\Resources\CampistaResource\CampistaExport;
use App\Models\Campista;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function campistaSensitiveHealthUser(bool $withSensitiveHealthPermission = false): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['view_any_campista', 'view_campista', 'export_campista'] as $permission) {
        Permission::findOrCreate($permission);
    }

    if ($withSensitiveHealthPermission) {
        Permission::findOrCreate('view_sensitive_health_campista');
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['view_any_campista', 'view_campista', 'export_campista']);

    if ($withSensitiveHealthPermission) {
        $user->givePermissionTo('view_sensitive_health_campista');
    }

    return $user;
}

function campistaWithSensitiveHealthDetails(): Campista
{
    return Campista::factory()->create([
        'avatar_url' => 'foto-formulario/test.png',
        'status' => StatusInscricao::Pago->value,
        'tribo_id' => null,
        'user_id' => null,
        'form_data' => [
            'data_nacimento' => '15/02/2000',
            'sexo' => 'M',
            'altura' => '170',
            'peso' => '70',
            'telefone_campista' => '(47) 9 9999-9999',
            'telefone_reponsavel_1' => '(47) 9 8888-8888',
            'telefone_reponsavel_nome_1' => 'Responsavel',
            'paroquia' => 0,
            'comunidade' => 1,
            'toma_remedio' => true,
            'remedio' => 'Dipirona a cada 8 horas',
            'tem_recomendacao' => true,
            'recomendacao' => 'Evitar amendoim',
            'tamanho_camiseta' => 'M',
        ],
    ]);
}

it('hides sensitive health details on the Campista view form without permission', function () {
    $user = campistaSensitiveHealthUser();
    $campista = campistaWithSensitiveHealthDetails();

    $this->actingAs($user)
        ->get(route('filament.admin.resources.campistas.view', ['record' => $campista]))
        ->assertOk()
        ->assertDontSee('Dipirona a cada 8 horas')
        ->assertDontSee('Evitar amendoim');
});

it('exports sensitive health columns only for authorized users', function () {
    $user = campistaSensitiveHealthUser();
    $authorizedUser = campistaSensitiveHealthUser(withSensitiveHealthPermission: true);

    $this->actingAs($user);
    $unauthorizedColumns = collect(CampistaExport::getExportColumns())
        ->map(fn ($column) => $column->getName())
        ->all();

    $this->actingAs($authorizedUser);
    $authorizedColumns = collect(CampistaExport::getExportColumns())
        ->map(fn ($column) => $column->getName())
        ->all();

    expect($unauthorizedColumns)
        ->not->toContain('form_data.remedio')
        ->not->toContain('form_data.recomendacao')
        ->and($authorizedColumns)
        ->toContain('form_data.remedio')
        ->toContain('form_data.recomendacao');
});
