<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use App\Models\Tribo;
use App\Models\User;
use App\Support\Reports\CampistaReportData;
use App\Support\Reports\CampistaReportType;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
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

function reportCampistaLinkedPayment(Campista $campista): Lancamento
{
    $category = CategoriaLancamento::factory()->create([
        'nome' => 'Inscrições relatório',
        'tipo' => TipoLacamento::Receita,
    ]);

    $lancamento = Lancamento::factory()->create([
        'nome' => 'Lançamento relatório inscrição',
        'data' => '2026-06-08 10:00:00',
        'valor' => 35000,
        'tipo' => TipoLacamento::Receita,
        'status' => StatusLacamento::Pago,
        'forma_pagamento' => FormaPagamento::Pix,
        'user_id' => null,
    ]);

    $lancamento->items()->create([
        'nome' => 'Inscrição relatório',
        'descricao' => 'Pagamento vinculado no relatório',
        'valor' => 35000,
        'categoria_lancamento_id' => $category->id,
        'registration_type' => $campista::class,
        'registration_id' => $campista->id,
    ]);

    return $lancamento;
}

function reportHtml(string $type, array $filters, User $user): string
{
    $filters = [
        'type' => $type,
        ...$filters,
    ];

    return view('admin.reports.print', [
        'report' => app(CampistaReportData::class)->payload(
            CampistaReportType::from($type),
            $filters,
            $user,
        ),
        'returnUrl' => route('filament.admin.pages.reports-page', $filters),
        'logoSrc' => asset('img/logo.png'),
    ])->render();
}

function assertPrintableHtml(TestResponse $response): TestResponse
{
    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/html')
        ->and($response->headers->get('content-disposition'))->toBeNull()
        ->and($response->getContent())->not->toStartWith('%PDF')
        ->and($response->getContent())->toContain('Prévia para impressão')
        ->toContain('data-report-print')
        ->toContain('data-report-save-pdf')
        ->toContain('data-report-action-icon="heroicon-s-printer"');

    return $response;
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
        ->tap(fn (TestResponse $response) => assertPrintableHtml($response));

    $this->actingAs($user)
        ->get(route('admin.reports.print', ['type' => 'sensitive_health']))
        ->assertForbidden();
});

it('renders the sensitive health disclosure control only for authorized report users', function () {
    $this->seed(ShieldSeeder::class);

    $superAdministrator = reportUserWithRole('Super Administrador');
    $administrator = reportUserWithRole('Administrador');
    $infirmary = reportUserWithRole('Enfermaria');

    $this->actingAs($superAdministrator)
        ->get(route('filament.admin.pages.reports-page'))
        ->assertOk()
        ->assertSee('Exibir dados de pagamento')
        ->assertSee('Por padrão, dados de pagamento permanecem ocultos');

    $this->actingAs($administrator)
        ->get(route('filament.admin.pages.reports-page'))
        ->assertOk()
        ->assertDontSee('Exibir dados médicos')
        ->assertDontSee('Dados médicos sensíveis')
        ->assertDontSee('Exibir dados de pagamento');

    $this->actingAs($infirmary)
        ->get(route('filament.admin.pages.reports-page'))
        ->assertOk()
        ->assertSee('Exibir dados médicos')
        ->assertSee('Por padrão, dados médicos permanecem ocultos')
        ->assertDontSee('Exibir dados de pagamento');
});

it('builds the report filters with native Filament form components', function () {
    $page = file_get_contents(app_path('Filament/Pages/ReportsPage.php'));
    $view = file_get_contents(resource_path('views/filament/pages/reports-page.blade.php'));
    $helpView = resource_path('views/filament/pages/partials/reports-help.blade.php');

    expect($page)
        ->toContain("Action::make('reportHelp')")
        ->toContain('->slideOver()')
        ->toContain("view('filament.pages.partials.reports-help'")
        ->toContain("Select::make('type')")
        ->toContain("Select::make('status')")
        ->toContain("Select::make('tribo_id')")
        ->toContain("Select::make('presenca')")
        ->toContain("Toggle::make('show_sensitive_health')")
        ->toContain("Toggle::make('show_payment_data')")
        ->toContain('Exibir dados de pagamento')
        ->toContain('Por padrão, dados de pagamento permanecem ocultos')
        ->toContain("Checkbox::make('confirm_sensitive_health')")
        ->toContain("->accepted()\n                                    ->live()")
        ->toContain('Dados médicos sensíveis')
        ->toContain("TextInput::make('search')")
        ->toContain('->footerActions([')
        ->not->toContain('->openUrlInNewTab()')
        ->toContain('->live()')
        ->toContain('->live(debounce: 500)')
        ->and($view)
        ->toContain('{{ $this->form }}')
        ->toContain('juvenil-report-loading')
        ->toContain('data-report-preview-loading')
        ->not->toContain('juvenil-report-grid')
        ->not->toContain('juvenil-report-card')
        ->not->toContain('<select')
        ->not->toContain('<input')
        ->and($helpView)
        ->toBeFile()
        ->and(file_get_contents($helpView))
        ->toContain('Como usar a central de relatórios')
        ->toContain('Fichas de inscrição')
        ->toContain('Quadrante das inscrições por tribo')
        ->toContain('Lista médica da enfermaria')
        ->toContain('Contatos e endereços para missão');
});

it('renders the printable preview as HTML in the same tab', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista();

    $administrator = reportUserWithRole('Administrador');
    $returnUrl = route('filament.admin.pages.reports-page', [
        'type' => 'registration_fichas',
        'status' => [StatusInscricao::Pago->value],
        'search' => 'Centro',
    ]);

    $response = $this->actingAs($administrator)
        ->get(route('admin.reports.print', [
            'type' => 'registration_fichas',
            'status' => [StatusInscricao::Pago->value],
            'search' => 'Centro',
            'return' => $returnUrl,
        ]));

    assertPrintableHtml($response);

    $html = reportHtml('registration_fichas', [
        'status' => [StatusInscricao::Pago->value],
        'search' => 'Centro',
    ], $administrator);

    expect($html)
        ->toContain('<title>Fichas de inscrição - '.e(config('app.name')).'</title>')
        ->toContain('<link rel="icon" type="image/png" href="'.asset('img/logo.png').'">')
        ->toContain('<body class="report-print-document">')
        ->toContain('report-print-toolbar')
        ->toContain('report-print-panel')
        ->toContain('report-print-filter')
        ->toContain('--report-screen-bg: #03181c')
        ->toContain('background: var(--report-screen-bg);')
        ->toContain('report-print-document')
        ->toContain('Salvar PDF')
        ->toContain('Imprimir')
        ->toContain('Voltar para a central')
        ->toContain('report-print-action__icon')
        ->toContain('data-report-action-icon="heroicon-s-arrow-left"')
        ->toContain('data-report-action-icon="heroicon-s-arrow-down-tray"')
        ->toContain('data-report-action-icon="heroicon-s-printer"')
        ->not->toContain('data-report-action-icon="heroicon-o-')
        ->toContain(e($returnUrl))
        ->toContain('Logo do acampamento')
        ->not->toContain('<dt>Central</dt>');
});

it('keeps printable previews on the HTML rendering path', function () {
    $controller = file_get_contents(app_path('Http/Controllers/Admin/PrintableReportController.php'));
    $printView = file_get_contents(resource_path('views/admin/reports/print.blade.php'));

    expect($controller)
        ->toContain("return view('admin.reports.print'")
        ->not->toContain('Pdf::')
        ->not->toContain('preparePdfRuntime')
        ->and($printView)
        ->toContain('report-print-toolbar')
        ->toContain('report-print-document')
        ->toContain('data-report-save-pdf')
        ->toContain('data-report-action-icon="heroicon-s-arrow-down-tray"')
        ->toContain('data-report-action-icon="heroicon-s-printer"')
        ->toContain('window.print()')
        ->toContain('-webkit-print-color-adjust: exact;')
        ->toContain('print-color-adjust: exact;')
        ->not->toContain('Salvar HTML')
        ->not->toContain('data-report-save>')
        ->not->toContain('new Blob')
        ->not->toContain('report-pdf-document');
});

it('renders the campista photo in printable registration fichas', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista([
        'avatar_url' => 'foto-formulario/ana.png',
    ]);

    $administrator = reportUserWithRole('Administrador');

    $html = reportHtml('registration_fichas', [], $administrator);

    expect($html)
        ->toContain('Foto de Ana Maria Juvenil')
        ->toContain('/storage/foto-formulario/ana.png');
});

it('applies ficha visual styling and keeps linked payments hidden by default on printable registration reports', function () {
    $this->seed(ShieldSeeder::class);

    $tribe = Tribo::query()->create(['cor' => 'Rosa', 'cor_hex' => '#123abc']);
    $campista = reportCampista([
        'tribo_id' => $tribe->id,
        'forma_pagamento' => FormaPagamento::Pix,
        'status' => StatusInscricao::Pago,
    ]);
    reportCampistaLinkedPayment($campista);
    $superAdministrator = reportUserWithRole('Super Administrador');

    $html = reportHtml('registration_fichas', [], $superAdministrator);

    expect($html)
        ->toContain('report-registration-ficha__bento')
        ->toContain('report-card--personal')
        ->toContain('report-card--contact')
        ->toContain('report-card--address')
        ->toContain('report-card--community')
        ->toContain('report-card--health')
        ->toContain('data-report-badge-icon="heroicon-s-flag"')
        ->toContain('--report-accent: #123abc')
        ->not->toContain('report-registration-summary')
        ->not->toContain('Resumo da inscrição')
        ->not->toContain('data-report-summary-icon')
        ->not->toContain('data-report-summary-color')
        ->not->toContain('class="report-badge report-badge--success"')
        ->not->toContain('class="report-badge report-badge--warning"')
        ->not->toContain('report-card--control')
        ->not->toContain('Controle da inscrição')
        ->not->toContain('Data de inscrição')
        ->not->toContain('Data de pagamento')
        ->not->toContain('<section class="report-card report-registration-payment-section">')
        ->not->toContain('Pagamentos vinculados')
        ->not->toContain('Lançamento relatório inscrição')
        ->not->toContain('Comprovantes anexados');

    $printView = file_get_contents(resource_path('views/admin/reports/print.blade.php'));

    expect($printView)
        ->toContain('report-fields-table')
        ->toContain('table-layout: fixed;')
        ->toContain('report-field__label')
        ->toContain('report-field__value')
        ->toContain('.report-card--payments')
        ->toContain('grid-column: 1 / -1;')
        ->toContain('margin-top: 0;')
        ->not->toContain('.report-registration-summary');
});

it('starts printable registration fichas after a report data cover page', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista();

    $administrator = reportUserWithRole('Administrador');

    $html = reportHtml('registration_fichas', [], $administrator);
    $printView = file_get_contents(resource_path('views/admin/reports/print.blade.php'));

    expect($html)
        ->toContain('aria-label="Dados do relatório"')
        ->toContain('report-cover')
        ->toContain('report-content--registration-fichas')
        ->toContain('Dados do relatório')
        ->toContain('Filtros aplicados')
        ->and(strpos($html, 'report-cover'))
        ->toBeLessThan(strpos($html, 'report-registration-ficha'))
        ->and($printView)
        ->toContain('.report-cover')
        ->toContain('break-after: page;')
        ->toContain('background: #fff7ed;')
        ->toContain('color: #7a2e04;')
        ->toContain('color: #9a3f00;');
});

it('marks the health section with a sensitive data badge only when medical data is exposed', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista();

    $infirmary = reportUserWithRole('Enfermaria');

    expect(reportHtml('registration_fichas', [], $infirmary))
        ->toContain('Saúde e cuidados')
        ->not->toContain('Dados sensíveis');

    expect(reportHtml('registration_fichas', [
        'show_sensitive_health' => 1,
        'confirm_sensitive_health' => 1,
    ], $infirmary))
        ->toContain('Saúde e cuidados')
        ->toContain('<span class="report-sensitive-badge">Dados sensíveis</span>');
});

it('shows linked payment badges on printable registration reports only when the financial toggle is enabled', function () {
    $this->seed(ShieldSeeder::class);

    $campista = reportCampista([
        'forma_pagamento' => FormaPagamento::Pix,
        'status' => StatusInscricao::Pago,
    ]);
    $lancamento = reportCampistaLinkedPayment($campista);
    $superAdministrator = reportUserWithRole('Super Administrador');

    $html = reportHtml('registration_fichas', [
        'show_payment_data' => 1,
    ], $superAdministrator);

    expect($html)
        ->toContain('report-registration-ficha__bento')
        ->toContain('<section class="report-card report-card--payments report-registration-payment-section">')
        ->toContain('Pagamentos vinculados')
        ->toContain('Lançamento relatório inscrição')
        ->toContain('R$ 350,00')
        ->toContain('08/06/2026')
        ->toContain('Visualizar lançamento')
        ->toContain(route('filament.admin.resources.lancamentos.view', ['record' => $lancamento]))
        ->toContain('data-report-payment-icon="fab-pix"')
        ->toContain('data-report-payment-color="teal"')
        ->toContain('data-report-payment-icon="polaris-payment-icon"')
        ->toContain('data-report-payment-color="success"')
        ->not->toContain('Comprovantes anexados');
});

it('hides linked payments on printable registration reports without financial view permissions', function () {
    $this->seed(ShieldSeeder::class);

    $campista = reportCampista();
    reportCampistaLinkedPayment($campista);
    $administrator = reportUserWithRole('Administrador');

    $html = reportHtml('registration_fichas', [
        'show_payment_data' => 1,
    ], $administrator);

    expect($html)
        ->not->toContain('report-registration-summary')
        ->not->toContain('<section class="report-card report-registration-payment-section">')
        ->not->toContain('Pagamentos vinculados')
        ->not->toContain('Lançamento relatório inscrição');
});

it('renders the camp logo in every printable report header', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista();

    $administrator = reportUserWithRole('Administrador');
    $infirmary = reportUserWithRole('Enfermaria');

    foreach (['registration_fichas', 'tribe_quadrant', 'mission_contacts'] as $type) {
        expect(reportHtml($type, [], $administrator))
            ->toContain('Logo do acampamento')
            ->toContain('/img/logo.png');
    }

    expect(reportHtml('sensitive_health', [], $infirmary))
        ->toContain('Logo do acampamento')
        ->toContain('/img/logo.png');
});

it('blocks the medical report for administrators and exposes it only to the infirmary', function () {
    $this->seed(ShieldSeeder::class);

    $tribe = Tribo::query()->create(['cor' => 'Azul', 'cor_hex' => '#123abc']);
    reportCampista(['tribo_id' => $tribe->id]);

    $administrator = reportUserWithRole('Administrador');
    $infirmary = reportUserWithRole('Enfermaria');

    $this->actingAs($administrator)
        ->get(route('admin.reports.print', ['type' => 'sensitive_health']))
        ->assertForbidden();

    expect(reportHtml('sensitive_health', [], $infirmary))
        ->toContain('Lista médica da enfermaria')
        ->toContain('Ana Maria Juvenil')
        ->toContain('report-table-tribe')
        ->toContain('--report-accent: #123abc')
        ->toContain('Informação restrita')
        ->not->toContain('Dipirona a cada 8 horas')
        ->not->toContain('Evitar amendoim');

    expect(reportHtml('sensitive_health', [
        'show_sensitive_health' => 1,
        'confirm_sensitive_health' => 1,
    ], $infirmary))
        ->toContain('Lista médica da enfermaria')
        ->toContain('Ana Maria Juvenil')
        ->toContain('Dipirona a cada 8 horas')
        ->toContain('Evitar amendoim');
});

it('requires the sensitive health toggle and confirmation before exposing medical data in any report', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista();

    $infirmary = reportUserWithRole('Enfermaria');

    $restrictedHtml = reportHtml('registration_fichas', [], $infirmary);

    expect($restrictedHtml)
        ->toContain('<span class="report-field__label">Toma remédio?</span>')
        ->toContain('<span class="report-field__label">Tem recomendação?</span>')
        ->toContain('Informação restrita')
        ->not->toContain('Dipirona a cada 8 horas')
        ->not->toContain('Evitar amendoim');

    expect(substr_count($restrictedHtml, '<strong class="report-field__value">Sim</strong>'))
        ->toBeGreaterThanOrEqual(2);

    expect(reportHtml('registration_fichas', [
        'show_sensitive_health' => 1,
    ], $infirmary))
        ->toContain('Informação restrita')
        ->not->toContain('Dipirona a cada 8 horas')
        ->not->toContain('Evitar amendoim');

    expect(reportHtml('registration_fichas', [
        'show_sensitive_health' => 1,
        'confirm_sensitive_health' => 1,
    ], $infirmary))
        ->toContain('Dipirona a cada 8 horas')
        ->toContain('Evitar amendoim');
});

it('does not leak medical details in non medical reports', function () {
    $this->seed(ShieldSeeder::class);

    reportCampista();

    $administrator = reportUserWithRole('Administrador');

    expect(reportHtml('mission_contacts', [], $administrator))
        ->toContain('Contatos e endereços para missão')
        ->toContain('Maria Responsavel')
        ->toContain('Rua da Missão')
        ->not->toContain('Dipirona a cada 8 horas')
        ->not->toContain('Evitar amendoim');

    expect(reportHtml('registration_fichas', [], $administrator))
        ->toContain('Fichas de inscrição')
        ->toContain('Informação restrita')
        ->not->toContain('Dipirona a cada 8 horas')
        ->not->toContain('Evitar amendoim');
});

it('renders the tribe quadrant grouped by tribe', function () {
    $this->seed(ShieldSeeder::class);

    $azul = Tribo::query()->create(['cor' => 'Azul', 'cor_hex' => '#123abc']);
    $verde = Tribo::query()->create(['cor' => 'Verde', 'cor_hex' => '#16a34a']);

    reportCampista(['nome' => 'Ana Azul', 'tribo_id' => $azul->id]);
    reportCampista(['nome' => 'Bruno Azul', 'tribo_id' => $azul->id]);
    reportCampista(['nome' => 'Carla Verde', 'tribo_id' => $verde->id]);

    $administrator = reportUserWithRole('Administrador');

    expect(reportHtml('tribe_quadrant', [], $administrator))
        ->toContain('Quadrante das inscrições por tribo')
        ->toContain('report-tribe-swatch')
        ->toContain('--report-accent: #123abc')
        ->toContain('Azul')
        ->toContain('2 campistas')
        ->toContain('Ana Azul')
        ->toContain('Bruno Azul')
        ->toContain('Verde')
        ->toContain('1 campista')
        ->toContain('Carla Verde');
});
