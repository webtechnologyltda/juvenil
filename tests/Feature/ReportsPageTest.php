<?php

use App\Enums\StatusInscricao;
use App\Models\Campista;
use App\Models\Tribo;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function reportCampista(array $overrides = []): Campista
{
    return Campista::factory()->create(array_replace_recursive([
        'nome' => 'Ana Maria Juvenil',
        'status' => StatusInscricao::Pago->value,
        'presenca' => false,
        'tribo_id' => null,
        'user_id' => null,
        'form_data' => [
            'data_nacimento' => '15/02/2009',
            'sexo' => 'F',
            'telefone_campista' => '(47) 9 9999-0000',
            'telefone_reponsavel_nome_1' => 'Maria Responsavel',
            'telefone_reponsavel_1' => '(47) 9 8888-0000',
            'cep' => '88370-000',
            'rua' => 'Rua da Missão',
            'numero' => '123',
            'bairro' => 'Centro',
            'cidade' => 'Navegantes',
            'estado' => 'SC',
            'ponto_referencia' => 'Casa azul',
            'paroquia' => 0,
            'comunidade' => 1,
            'toma_remedio' => true,
            'remedio' => 'Dipirona a cada 8 horas',
            'tem_recomendacao' => true,
            'recomendacao' => 'Evitar amendoim',
            'tamanho_camiseta' => 'M',
            'declaro' => true,
        ],
    ], $overrides));
}

function reportUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('seeds report permissions with least privilege by role', function () {
    $this->seed(ShieldSeeder::class);

    expect(Permission::query()->where('name', 'page_reports_page')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'print_registration_fichas_report')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'print_tribe_quadrant_report')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'print_mission_contacts_report')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'print_sensitive_health_report')->exists())->toBeTrue()
        ->and(Role::findByName('Administrador')->hasPermissionTo('page_reports_page'))->toBeTrue()
        ->and(Role::findByName('Administrador')->hasPermissionTo('print_registration_fichas_report'))->toBeTrue()
        ->and(Role::findByName('Administrador')->hasPermissionTo('print_tribe_quadrant_report'))->toBeTrue()
        ->and(Role::findByName('Administrador')->hasPermissionTo('print_mission_contacts_report'))->toBeTrue()
        ->and(Role::findByName('Administrador')->hasPermissionTo('print_sensitive_health_report'))->toBeFalse()
        ->and(Role::findByName('Enfermaria')->hasPermissionTo('page_reports_page'))->toBeTrue()
        ->and(Role::findByName('Enfermaria')->hasPermissionTo('print_sensitive_health_report'))->toBeTrue()
        ->and(Role::findByName('Enfermaria')->hasPermissionTo('print_mission_contacts_report'))->toBeFalse()
        ->and(Role::findByName('Financeiro')->hasPermissionTo('page_reports_page'))->toBeFalse();
});

it('renders the reports launcher only for authorized operational users', function () {
    $this->seed(ShieldSeeder::class);

    $administrator = reportUserWithRole('Administrador');
    $finance = reportUserWithRole('Financeiro');

    $this->actingAs($administrator)
        ->get(route('filament.admin.pages.reports-page'))
        ->assertOk()
        ->assertSee('Relatórios dinâmicos')
        ->assertSee('Fichas de inscrição')
        ->assertSee('Quadrante por tribo')
        ->assertSee('Contatos e endereços')
        ->assertDontSee('Lista médica da enfermaria');

    $this->actingAs($finance)
        ->get(route('filament.admin.pages.reports-page'))
        ->assertForbidden();
});

it('uses the reports page permission to expose standard operational reports', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista();

    $role = Role::query()->create([
        'name' => 'Apoio operacional sem permissoes individuais',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo('page_reports_page');

    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)
        ->get(route('filament.admin.pages.reports-page'))
        ->assertOk()
        ->assertSee('Fichas de inscrição')
        ->assertSee('Quadrante por tribo')
        ->assertSee('Contatos e endereços')
        ->assertDontSee('Nenhum relatório disponível')
        ->assertDontSee('Lista médica da enfermaria');

    $this->actingAs($user)
        ->get(route('admin.reports.print', ['type' => 'registration_fichas']))
        ->assertOk()
        ->assertSee('Fichas de inscrição');

    $this->actingAs($user)
        ->get(route('admin.reports.print', ['type' => 'sensitive_health']))
        ->assertForbidden();
});

it('renders the sensitive health disclosure control only for authorized report users', function () {
    $this->seed(ShieldSeeder::class);

    $administrator = reportUserWithRole('Administrador');
    $infirmary = reportUserWithRole('Enfermaria');

    $this->actingAs($administrator)
        ->get(route('filament.admin.pages.reports-page'))
        ->assertOk()
        ->assertDontSee('Exibir dados médicos')
        ->assertDontSee('Dados médicos sensíveis');

    $this->actingAs($infirmary)
        ->get(route('filament.admin.pages.reports-page'))
        ->assertOk()
        ->assertSee('Exibir dados médicos')
        ->assertSee('Por padrão, dados médicos permanecem ocultos');
});

it('builds the report filters with native Filament form components', function () {
    $page = file_get_contents(app_path('Filament/Pages/ReportsPage.php'));
    $view = file_get_contents(resource_path('views/filament/pages/reports-page.blade.php'));

    expect($page)
        ->toContain("Select::make('type')")
        ->toContain("Select::make('status')")
        ->toContain("Select::make('tribo_id')")
        ->toContain("Select::make('presenca')")
        ->toContain("Toggle::make('show_sensitive_health')")
        ->toContain("Checkbox::make('confirm_sensitive_health')")
        ->toContain('Dados médicos sensíveis')
        ->toContain("TextInput::make('search')")
        ->toContain('->footerActions([')
        ->toContain('->openUrlInNewTab()')
        ->toContain('->live()')
        ->toContain('->live(debounce: 500)')
        ->and($view)
        ->toContain('{{ $this->form }}')
        ->not->toContain('<select')
        ->not->toContain('<input');
});

it('renders the campista photo in printable registration fichas', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista([
        'avatar_url' => 'foto-formulario/ana.png',
    ]);

    $administrator = reportUserWithRole('Administrador');

    $this->actingAs($administrator)
        ->get(route('admin.reports.print', ['type' => 'registration_fichas']))
        ->assertOk()
        ->assertSee('Foto de Ana Maria Juvenil')
        ->assertSee('/storage/foto-formulario/ana.png', false);
});

it('renders the camp logo in every printable report header', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista();

    $administrator = reportUserWithRole('Administrador');
    $infirmary = reportUserWithRole('Enfermaria');

    foreach (['registration_fichas', 'tribe_quadrant', 'mission_contacts'] as $type) {
        $this->actingAs($administrator)
            ->get(route('admin.reports.print', ['type' => $type]))
            ->assertOk()
            ->assertSee('Logo do acampamento')
            ->assertSee('/img/logo.png', false);
    }

    $this->actingAs($infirmary)
        ->get(route('admin.reports.print', ['type' => 'sensitive_health']))
        ->assertOk()
        ->assertSee('Logo do acampamento')
        ->assertSee('/img/logo.png', false);
});

it('blocks the medical report for administrators and exposes it only to the infirmary', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista();

    $administrator = reportUserWithRole('Administrador');
    $infirmary = reportUserWithRole('Enfermaria');

    $this->actingAs($administrator)
        ->get(route('admin.reports.print', ['type' => 'sensitive_health']))
        ->assertForbidden();

    $this->actingAs($infirmary)
        ->get(route('admin.reports.print', ['type' => 'sensitive_health']))
        ->assertOk()
        ->assertSee('Lista médica da enfermaria')
        ->assertSee('Ana Maria Juvenil')
        ->assertSee('Informação restrita')
        ->assertDontSee('Dipirona a cada 8 horas')
        ->assertDontSee('Evitar amendoim');

    $this->actingAs($infirmary)
        ->get(route('admin.reports.print', [
            'type' => 'sensitive_health',
            'show_sensitive_health' => 1,
            'confirm_sensitive_health' => 1,
        ]))
        ->assertOk()
        ->assertSee('Lista médica da enfermaria')
        ->assertSee('Ana Maria Juvenil')
        ->assertSee('Dipirona a cada 8 horas')
        ->assertSee('Evitar amendoim');
});

it('requires the sensitive health toggle and confirmation before exposing medical data in any report', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista();

    $infirmary = reportUserWithRole('Enfermaria');

    $this->actingAs($infirmary)
        ->get(route('admin.reports.print', ['type' => 'registration_fichas']))
        ->assertOk()
        ->assertSee('Informação restrita')
        ->assertDontSee('Dipirona a cada 8 horas')
        ->assertDontSee('Evitar amendoim');

    $this->actingAs($infirmary)
        ->get(route('admin.reports.print', [
            'type' => 'registration_fichas',
            'show_sensitive_health' => 1,
        ]))
        ->assertOk()
        ->assertSee('Informação restrita')
        ->assertDontSee('Dipirona a cada 8 horas')
        ->assertDontSee('Evitar amendoim');

    $this->actingAs($infirmary)
        ->get(route('admin.reports.print', [
            'type' => 'registration_fichas',
            'show_sensitive_health' => 1,
            'confirm_sensitive_health' => 1,
        ]))
        ->assertOk()
        ->assertSee('Dipirona a cada 8 horas')
        ->assertSee('Evitar amendoim');
});

it('does not leak medical details in non medical reports', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista();

    $administrator = reportUserWithRole('Administrador');

    $this->actingAs($administrator)
        ->get(route('admin.reports.print', ['type' => 'mission_contacts']))
        ->assertOk()
        ->assertSee('Contatos e endereços para missão')
        ->assertSee('Maria Responsavel')
        ->assertSee('Rua da Missão')
        ->assertDontSee('Dipirona a cada 8 horas')
        ->assertDontSee('Evitar amendoim');

    $this->actingAs($administrator)
        ->get(route('admin.reports.print', ['type' => 'registration_fichas']))
        ->assertOk()
        ->assertSee('Fichas de inscrição')
        ->assertSee('Informação restrita')
        ->assertDontSee('Dipirona a cada 8 horas')
        ->assertDontSee('Evitar amendoim');
});

it('renders the tribe quadrant grouped by tribe', function () {
    $this->seed(ShieldSeeder::class);

    $azul = Tribo::query()->create(['cor' => 'Azul']);
    $verde = Tribo::query()->create(['cor' => 'Verde']);

    reportCampista(['nome' => 'Ana Azul', 'tribo_id' => $azul->id]);
    reportCampista(['nome' => 'Bruno Azul', 'tribo_id' => $azul->id]);
    reportCampista(['nome' => 'Carla Verde', 'tribo_id' => $verde->id]);

    $administrator = reportUserWithRole('Administrador');

    $this->actingAs($administrator)
        ->get(route('admin.reports.print', ['type' => 'tribe_quadrant']))
        ->assertOk()
        ->assertSee('Quadrante das inscrições por tribo')
        ->assertSee('Azul')
        ->assertSee('2 campistas')
        ->assertSee('Ana Azul')
        ->assertSee('Bruno Azul')
        ->assertSee('Verde')
        ->assertSee('1 campista')
        ->assertSee('Carla Verde');
});
