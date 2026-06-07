# Operational Dashboard Widgets Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Operational Event dashboard for camp registration data with Apex charts, global filters, action-oriented widgets, and protected sensitive health data.

**Architecture:** Keep the dashboard page responsible for global filters and widget registration. Centralize query/filter/count logic in `App\Support\Dashboard\OperationalDashboardData`, then keep each Filament widget small and display-focused. Protect health details through a dedicated permission that applies to dashboard widgets, resource fields/tables, and exports.

**Tech Stack:** Laravel 13, Filament 5, Filament Shield, Livewire 4, Pest 4, `leandrocfe/filament-apex-charts`.

---

## File Structure

- Modify: `composer.json`, `composer.lock` to install `leandrocfe/filament-apex-charts:^5.0`.
- Modify: `app/Providers/Filament/AdminPanelProvider.php` to register `FilamentApexChartsPlugin::make()`.
- Modify: `app/Filament/Dashboard.php` to add global filters and explicit operational widgets.
- Create: `app/Support/Dashboard/OperationalDashboardData.php` for shared dashboard calculations.
- Create: `app/Support/Dashboard/OperationalDashboardFilters.php` for filter normalization constants.
- Create: `app/Filament/Widgets/Operational/OperationalPipelineStats.php`.
- Create: `app/Filament/Widgets/Operational/OperationalFunnelChart.php`.
- Create: `app/Filament/Widgets/Operational/RegistrationTrendChart.php`.
- Create: `app/Filament/Widgets/Operational/ShirtSizeChart.php`.
- Create: `app/Filament/Widgets/Operational/TribeDistributionChart.php`.
- Create: `app/Filament/Widgets/Operational/CommunityDistributionChart.php`.
- Create: `app/Filament/Widgets/Operational/DemographicsChart.php`.
- Create: `app/Filament/Widgets/Operational/SensitiveHealthSummary.php`.
- Create: `app/Filament/Widgets/Operational/SensitiveHealthTable.php`.
- Create: `app/Filament/Widgets/Operational/OperationalPendingTasksTable.php`.
- Modify: old widget classes under `app/Filament/Widgets/` so they are no longer shown on the main dashboard, or remove their dashboard visibility while preserving classes only if still referenced by permissions.
- Modify: `database/seeders/ShieldSeeder.php` to include `view_sensitive_health_campista` and default roles `Administrador` and `Enfermaria`.
- Modify: `app/Enums/RoleEnum.php` to include `Administrador` and `Enfermaria`.
- Modify: `app/Policies/CampistaPolicy.php` to add a `viewSensitiveHealth` policy method.
- Modify: `app/Filament/Resources/CampistaResource/CampistaForm.php` and `app/Filament/Resources/CampistaResource/CampistaTable.php` to hide or mask sensitive health details without permission.
- Modify: `app/Filament/Resources/CampistaResource/CampistaExport.php` to omit sensitive export columns unless authorized.
- Create: `tests/Feature/OperationalDashboardDataTest.php`.
- Create: `tests/Feature/OperationalDashboardSecurityTest.php`.
- Modify: `tests/Feature/PublicBrandPagesTest.php` or create a focused panel config test to assert Apex plugin registration.

## Task 1: Install And Register Apex Charts

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Test: `tests/Feature/OperationalDashboardSecurityTest.php`

- [ ] **Step 1: Write the failing panel plugin test**

Add this test to `tests/Feature/OperationalDashboardSecurityTest.php`:

```php
<?php

use Filament\Facades\Filament;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

it('registers the Apex Charts plugin on the admin panel', function () {
    $panel = Filament::getPanel('admin');

    expect($panel->getPlugin('filament-apex-charts'))
        ->toBeInstanceOf(FilamentApexChartsPlugin::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
php artisan test tests/Feature/OperationalDashboardSecurityTest.php --filter="registers the Apex Charts plugin"
```

Expected: FAIL because the package class or plugin registration is missing.

- [ ] **Step 3: Install package**

Run:

```bash
composer require leandrocfe/filament-apex-charts:"^5.0" --with-all-dependencies
```

Expected: Composer updates `composer.json` and `composer.lock`.

- [ ] **Step 4: Register plugin**

Modify `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
```

Add to the existing `->plugins([...])` list:

```php
FilamentApexChartsPlugin::make(),
```

- [ ] **Step 5: Verify test passes**

Run:

```bash
php artisan test tests/Feature/OperationalDashboardSecurityTest.php --filter="registers the Apex Charts plugin"
```

Expected: PASS.

## Task 2: Build Shared Operational Dashboard Data Service

**Files:**
- Create: `app/Support/Dashboard/OperationalDashboardFilters.php`
- Create: `app/Support/Dashboard/OperationalDashboardData.php`
- Test: `tests/Feature/OperationalDashboardDataTest.php`

- [ ] **Step 1: Write failing tests for dashboard calculations**

Create `tests/Feature/OperationalDashboardDataTest.php`:

```php
<?php

use App\Enums\StatusInscricao;
use App\Models\Campista;
use App\Models\Tribo;
use App\Support\Dashboard\OperationalDashboardData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeDashboardCampista(array $attributes = [], array $formData = []): Campista
{
    return Campista::query()->create(array_merge([
        'nome' => fake()->name(),
        'avatar_url' => 'foto-formulario/test.png',
        'status' => StatusInscricao::Pendente->value,
        'presenca' => false,
        'form_data' => array_merge([
            'data_nacimento' => '10/06/1990',
            'sexo' => 'M',
            'telefone_campista' => '(11) 9 9999-9999',
            'telefone_reponsavel_1' => '(11) 9 8888-8888',
            'telefone_reponsavel_nome_1' => 'Responsavel',
            'paroquia' => 0,
            'comunidade' => 1,
            'tamanho_camiseta' => 'M',
            'toma_remedio' => false,
            'tem_recomendacao' => false,
        ], $formData),
    ], $attributes));
}

it('calculates the operational pipeline excluding cancelled records by default', function () {
    makeDashboardCampista(['status' => StatusInscricao::Pendente->value]);
    makeDashboardCampista(['status' => StatusInscricao::Pago->value, 'presenca' => false]);
    makeDashboardCampista(['status' => StatusInscricao::Pago->value, 'presenca' => true]);
    makeDashboardCampista(['status' => StatusInscricao::Cancelado->value]);

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

it('applies status tribe community and presence filters to operational metrics', function () {
    $blue = Tribo::query()->create(['cor' => 'Azul']);
    $red = Tribo::query()->create(['cor' => 'Vermelha']);

    makeDashboardCampista(['status' => StatusInscricao::Pago->value, 'presenca' => false, 'tribo_id' => $blue->id], ['paroquia' => 0, 'comunidade' => 2]);
    makeDashboardCampista(['status' => StatusInscricao::Pago->value, 'presenca' => true, 'tribo_id' => $blue->id], ['paroquia' => 0, 'comunidade' => 2]);
    makeDashboardCampista(['status' => StatusInscricao::Pago->value, 'presenca' => false, 'tribo_id' => $red->id], ['paroquia' => 1, 'comunidade' => 3]);

    $data = app(OperationalDashboardData::class)->forFilters([
        'status' => [StatusInscricao::Pago->value],
        'tribo_id' => [$blue->id],
        'paroquia' => '0',
        'comunidade' => '2',
        'presenca' => '0',
    ]);

    expect($data->pipeline()['valid'])->toBe(1)
        ->and($data->pipeline()['awaiting_check_in'])->toBe(1)
        ->and($data->tribes())->toBe(['Azul' => 1]);
});

it('handles incomplete form data without exceptions', function () {
    makeDashboardCampista(['status' => StatusInscricao::Pago->value], []);
    Campista::query()->create([
        'nome' => 'Registro incompleto',
        'status' => StatusInscricao::Pago->value,
        'presenca' => false,
        'form_data' => [],
    ]);

    $data = app(OperationalDashboardData::class)->forFilters([]);

    expect($data->shirts())->toHaveKey('Sem tamanho')
        ->and($data->communities())->toHaveKey('Sem comunidade')
        ->and($data->ages())->toHaveKey('Sem data');
});

it('detects operational pending tasks without treating tribe as campista data debt', function () {
    makeDashboardCampista([], [
        'telefone_campista' => '',
        'telefone_reponsavel_1' => '',
        'telefone_reponsavel_nome_1' => '',
        'paroquia' => null,
        'comunidade' => null,
        'tamanho_camiseta' => '',
    ]);

    $pending = app(OperationalDashboardData::class)->forFilters([])->pendingTasks()->first();

    expect($pending['issues'])->toContain('Sem telefone do campista')
        ->toContain('Sem telefone do responsavel')
        ->toContain('Sem nome do responsavel')
        ->toContain('Sem paroquia/comunidade')
        ->toContain('Sem tamanho de camiseta')
        ->not->toContain('Sem tribo');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
php artisan test tests/Feature/OperationalDashboardDataTest.php
```

Expected: FAIL because `OperationalDashboardData` does not exist.

- [ ] **Step 3: Implement filter helper**

Create `app/Support/Dashboard/OperationalDashboardFilters.php`:

```php
<?php

namespace App\Support\Dashboard;

use App\Enums\StatusInscricao;

class OperationalDashboardFilters
{
    public static function statusValues(array $filters): array
    {
        $values = $filters['status'] ?? [];
        $values = is_array($values) ? $values : [$values];

        return collect($values)
            ->filter(fn ($value): bool => $value !== null && $value !== '')
            ->map(fn ($value): int => (int) $value)
            ->values()
            ->all();
    }

    public static function tribeIds(array $filters): array
    {
        $values = $filters['tribo_id'] ?? [];
        $values = is_array($values) ? $values : [$values];

        return collect($values)
            ->filter(fn ($value): bool => $value !== null && $value !== '')
            ->map(fn ($value): int => (int) $value)
            ->values()
            ->all();
    }

    public static function validStatuses(array $filters): array
    {
        $statuses = self::statusValues($filters);

        return $statuses === []
            ? [StatusInscricao::Pendente->value, StatusInscricao::Pago->value]
            : $statuses;
    }
}
```

- [ ] **Step 4: Implement shared service**

Create `app/Support/Dashboard/OperationalDashboardData.php` with these public methods:

```php
<?php

namespace App\Support\Dashboard;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OperationalDashboardData
{
    public function forFilters(array $filters): OperationalDashboardDataSet
    {
        return new OperationalDashboardDataSet($this->records($filters), $this->allRecords($filters));
    }

    protected function records(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->whereIn('status', OperationalDashboardFilters::validStatuses($filters))
            ->with('tribo')
            ->get();
    }

    protected function allRecords(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->with('tribo')
            ->get();
    }

    protected function baseQuery(array $filters): Builder
    {
        return Campista::query()
            ->when(OperationalDashboardFilters::tribeIds($filters) !== [], fn (Builder $query) => $query->whereIn('tribo_id', OperationalDashboardFilters::tribeIds($filters)))
            ->when(($filters['paroquia'] ?? '') !== '', fn (Builder $query) => $query->where('form_data->paroquia', $filters['paroquia']))
            ->when(($filters['comunidade'] ?? '') !== '', fn (Builder $query) => $query->where('form_data->comunidade', $filters['comunidade']))
            ->when(($filters['presenca'] ?? '') !== '', fn (Builder $query) => $query->where('presenca', (bool) (int) $filters['presenca']));
    }
}

class OperationalDashboardDataSet
{
    public function __construct(
        private readonly Collection $records,
        private readonly Collection $allRecords,
    ) {}

    public function pipeline(): array
    {
        return [
            'valid' => $this->records->count(),
            'pending_payment' => $this->records->where('status', StatusInscricao::Pendente)->count(),
            'paid' => $this->records->where('status', StatusInscricao::Pago)->count(),
            'awaiting_check_in' => $this->records->where('status', StatusInscricao::Pago)->where('presenca', false)->count(),
            'present' => $this->records->where('status', StatusInscricao::Pago)->where('presenca', true)->count(),
            'cancelled' => $this->allRecords->where('status', StatusInscricao::Cancelado)->count(),
        ];
    }

    public function tribes(): array
    {
        return $this->records
            ->groupBy(fn (Campista $campista): string => $campista->tribo?->cor ?: 'Sem tribo')
            ->map->count()
            ->sortDesc()
            ->all();
    }

    public function shirts(): array
    {
        return $this->records
            ->groupBy(fn (Campista $campista): string => $this->formValue($campista, 'tamanho_camiseta') ?: 'Sem tamanho')
            ->map->count()
            ->sortDesc()
            ->all();
    }

    public function communities(): array
    {
        return $this->records
            ->groupBy(fn (Campista $campista): string => $this->communityLabel($campista))
            ->map->count()
            ->sortDesc()
            ->all();
    }

    public function ages(): array
    {
        return $this->records
            ->groupBy(fn (Campista $campista): string => $this->ageBucket($campista))
            ->map->count()
            ->all();
    }

    public function sexes(): array
    {
        return $this->records
            ->groupBy(fn (Campista $campista): string => match ($this->formValue($campista, 'sexo')) {
                'M' => 'Masculino',
                'F' => 'Feminino',
                default => 'Sem sexo',
            })
            ->map->count()
            ->all();
    }

    public function healthSummary(): array
    {
        $medicine = $this->records->filter(fn (Campista $campista): bool => $this->truthy($this->formValue($campista, 'toma_remedio')));
        $recommendation = $this->records->filter(fn (Campista $campista): bool => $this->truthy($this->formValue($campista, 'tem_recomendacao')));

        return [
            'medicine' => $medicine->count(),
            'recommendation' => $recommendation->count(),
            'both' => $medicine->intersect($recommendation)->count(),
        ];
    }

    public function sensitiveHealthRecords(): Collection
    {
        return $this->records
            ->filter(fn (Campista $campista): bool => $this->truthy($this->formValue($campista, 'toma_remedio')) || $this->truthy($this->formValue($campista, 'tem_recomendacao')))
            ->values();
    }

    public function pendingTasks(): Collection
    {
        return $this->records
            ->map(function (Campista $campista): array {
                $issues = collect([
                    $this->blank($this->formValue($campista, 'telefone_campista')) ? 'Sem telefone do campista' : null,
                    $this->blank($this->formValue($campista, 'telefone_reponsavel_1')) ? 'Sem telefone do responsavel' : null,
                    $this->blank($this->formValue($campista, 'telefone_reponsavel_nome_1')) ? 'Sem nome do responsavel' : null,
                    $this->blank($this->formValue($campista, 'paroquia')) || $this->blank($this->formValue($campista, 'comunidade')) ? 'Sem paroquia/comunidade' : null,
                    $this->blank($this->formValue($campista, 'tamanho_camiseta')) ? 'Sem tamanho de camiseta' : null,
                    $this->blank($campista->avatar_url) ? 'Sem foto' : null,
                ])->filter()->values()->all();

                return ['campista' => $campista, 'issues' => $issues];
            })
            ->filter(fn (array $row): bool => $row['issues'] !== [])
            ->values();
    }

    public function registrationsByDay(): array
    {
        return $this->records
            ->groupBy(fn (Campista $campista): string => $campista->created_at?->format('d/m') ?? 'Sem data')
            ->map->count()
            ->all();
    }

    private function formValue(Campista $campista, string $key): mixed
    {
        return data_get($campista->form_data ?? [], $key);
    }

    private function communityLabel(Campista $campista): string
    {
        $paroquia = $this->formValue($campista, 'paroquia');
        $comunidade = $this->formValue($campista, 'comunidade');

        if ($this->blank($paroquia) || $this->blank($comunidade)) {
            return 'Sem comunidade';
        }

        return 'Paroquia '.$paroquia.' / Comunidade '.$comunidade;
    }

    private function ageBucket(Campista $campista): string
    {
        $date = $this->formValue($campista, 'data_nacimento');

        try {
            $age = Carbon::createFromFormat('d/m/Y', (string) $date)->age;
        } catch (\Throwable) {
            return 'Sem data';
        }

        return match (true) {
            $age < 30 => 'Ate 29',
            $age <= 39 => '30-39',
            $age <= 49 => '40-49',
            $age <= 59 => '50-59',
            default => '60+',
        };
    }

    private function truthy(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1';
    }

    private function blank(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
```

- [ ] **Step 5: Run data tests**

Run:

```bash
php artisan test tests/Feature/OperationalDashboardDataTest.php
```

Expected: PASS.

## Task 3: Add Sensitive Health Permission And Roles

**Files:**
- Modify: `app/Enums/RoleEnum.php`
- Modify: `database/seeders/ShieldSeeder.php`
- Modify: `app/Policies/CampistaPolicy.php`
- Test: `tests/Feature/OperationalDashboardSecurityTest.php`

- [ ] **Step 1: Write failing permission and role tests**

Append to `tests/Feature/OperationalDashboardSecurityTest.php`:

```php
use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('seeds administrator and infirmary roles with least privilege health permissions', function () {
    $this->seed(ShieldSeeder::class);

    expect(Role::query()->where('name', 'Administrador')->exists())->toBeTrue()
        ->and(Role::query()->where('name', 'Enfermaria')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'view_sensitive_health_campista')->exists())->toBeTrue()
        ->and(Role::findByName('Administrador')->hasPermissionTo('view_sensitive_health_campista'))->toBeFalse()
        ->and(Role::findByName('Enfermaria')->hasPermissionTo('view_sensitive_health_campista'))->toBeTrue()
        ->and(Role::findByName('Super Administrador')->hasPermissionTo('view_sensitive_health_campista'))->toBeTrue();
});

it('exposes role enum labels for administrator and infirmary', function () {
    expect(RoleEnum::getRoleEnumDescription(RoleEnum::Administrador))->toBe('Administrador')
        ->and(RoleEnum::getRoleEnumDescription(RoleEnum::Enfermaria))->toBe('Enfermaria');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
php artisan test tests/Feature/OperationalDashboardSecurityTest.php --filter="administrator and infirmary|role enum"
```

Expected: FAIL because roles/enum cases do not exist.

- [ ] **Step 3: Add enum cases**

Modify `app/Enums/RoleEnum.php`:

```php
case SuperAdministrador = 1;
case UsuarioComum = 2;
case Financeiro = 3;
case Administrador = 4;
case Enfermaria = 5;
```

Update all match expressions so id `4` maps to `Administrador`, id `5` maps to `Enfermaria`, colors are `orange` and `teal`, and labels are `Administrador` and `Enfermaria`.

- [ ] **Step 4: Add custom permission and role seeding**

Modify `database/seeders/ShieldSeeder.php`:

```php
protected static function customPermissionNames(): array
{
    return [
        'audit_campista',
        'audit_tribo',
        'export_campista',
        'restoreAudit_campista',
        'restoreAudit_tribo',
        'updateTribo_campista',
        'view_sensitive_health_campista',
    ];
}
```

After creating Super Administrador, create `Administrador` and `Enfermaria`. Assign `view_sensitive_health_campista` only to `Enfermaria` and `Super Administrador`. Give `Administrador` operational resource/widget permissions but not finance or sensitive health permissions.

- [ ] **Step 5: Add policy method**

Modify `app/Policies/CampistaPolicy.php`:

```php
public function viewSensitiveHealth(AuthUser $authUser): bool
{
    return $authUser->can('view_sensitive_health_campista');
}
```

- [ ] **Step 6: Run tests**

Run:

```bash
php artisan test tests/Feature/OperationalDashboardSecurityTest.php --filter="administrator and infirmary|role enum"
```

Expected: PASS.

## Task 4: Protect Sensitive Health Fields In Resource And Export

**Files:**
- Modify: `app/Filament/Resources/CampistaResource/CampistaForm.php`
- Modify: `app/Filament/Resources/CampistaResource/CampistaTable.php`
- Modify: `app/Filament/Resources/CampistaResource/CampistaExport.php`
- Test: `tests/Feature/OperationalDashboardSecurityTest.php`

- [ ] **Step 1: Write failing security tests**

Append to `tests/Feature/OperationalDashboardSecurityTest.php`:

```php
use App\Filament\Resources\CampistaResource\CampistaExport;
use App\Filament\Resources\CampistaResource\CampistaForm;
use App\Filament\Resources\CampistaResource\CampistaTable;

it('guards sensitive health fields in form table and export definitions', function () {
    $form = collect(CampistaForm::getFormInformacoesImportantes())->map(fn ($component) => method_exists($component, 'getName') ? $component->getName() : null);
    $tableSource = file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaTable.php'));
    $exportSource = file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaExport.php'));

    expect($tableSource)
        ->toContain('view_sensitive_health_campista')
        ->and($exportSource)
        ->toContain('view_sensitive_health_campista')
        ->and(file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaForm.php')))
        ->toContain('view_sensitive_health_campista');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
php artisan test tests/Feature/OperationalDashboardSecurityTest.php --filter="guards sensitive health fields"
```

Expected: FAIL because resources do not check the permission.

- [ ] **Step 3: Guard form fields**

In `CampistaForm`, for sensitive fields, add visibility/disabled rules:

```php
->visible(fn (): bool => auth()->user()?->can('view_sensitive_health_campista') ?? false)
```

Apply to text/detail fields `form_data.remedio` and `form_data.recomendacao`. The boolean indicators may remain visible when needed, but details must be hidden without permission.

- [ ] **Step 4: Guard table/export**

In `CampistaTable`, hide text/detail columns that expose sensitive health details unless authorized. In `CampistaExport`, wrap sensitive export columns so they are only returned when the current user can `view_sensitive_health_campista`.

- [ ] **Step 5: Run security test**

Run:

```bash
php artisan test tests/Feature/OperationalDashboardSecurityTest.php --filter="guards sensitive health fields"
```

Expected: PASS.

## Task 5: Build Operational Dashboard Widgets

**Files:**
- Modify: `app/Filament/Dashboard.php`
- Create: widget files under `app/Filament/Widgets/Operational/`
- Modify: old widgets under `app/Filament/Widgets/`
- Test: `tests/Feature/OperationalDashboardDataTest.php`

- [ ] **Step 1: Write failing dashboard widget registration test**

Append to `tests/Feature/OperationalDashboardDataTest.php`:

```php
use App\Filament\Dashboard;
use App\Filament\Widgets\Operational\OperationalPipelineStats;
use App\Filament\Widgets\Operational\OperationalFunnelChart;
use App\Filament\Widgets\Operational\SensitiveHealthSummary;

it('registers the operational widget set on the dashboard', function () {
    $widgets = Dashboard::getWidgets();

    expect($widgets)
        ->toContain(OperationalPipelineStats::class)
        ->toContain(OperationalFunnelChart::class)
        ->toContain(SensitiveHealthSummary::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
php artisan test tests/Feature/OperationalDashboardDataTest.php --filter="registers the operational widget set"
```

Expected: FAIL because widgets do not exist or are not registered.

- [ ] **Step 3: Add dashboard filters**

Modify `app/Filament/Dashboard.php` to use Filament dashboard filters and return form controls for status, tribe, parish/community and presence. Use existing enums/models for options.

- [ ] **Step 4: Create widgets**

Create operational widgets:

- `OperationalPipelineStats`: `StatsOverviewWidget`, uses `pipeline()`.
- `SensitiveHealthSummary`: `StatsOverviewWidget`, uses `healthSummary()` and never shows names/details.
- `SensitiveHealthTable`: `TableWidget`, visible only with `view_sensitive_health_campista`, uses `sensitiveHealthRecords()` and does not show full `remedio`/`recomendacao` text in dashboard.
- `OperationalPendingTasksTable`: `TableWidget`, lists campista name and issues from `pendingTasks()`.
- Apex widgets extending `Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget`: funnel, trend, shirts, tribe, community, demographics.

- [ ] **Step 5: Disable old dashboard widgets**

Old loose dashboard widgets should not render on the main dashboard after the new operational set is explicit. Prefer `Dashboard::getWidgets()` returning only operational classes. If old classes remain for permissions/history, keep them unregistered from dashboard display.

- [ ] **Step 6: Run widget registration test**

Run:

```bash
php artisan test tests/Feature/OperationalDashboardDataTest.php --filter="registers the operational widget set"
```

Expected: PASS.

## Task 6: Final Verification

**Files:**
- All files touched above

- [ ] **Step 1: Run focused tests**

Run:

```bash
php artisan test tests/Feature/OperationalDashboardDataTest.php tests/Feature/OperationalDashboardSecurityTest.php
```

Expected: PASS.

- [ ] **Step 2: Run existing relevant tests**

Run:

```bash
php artisan test tests/Feature/PublicBrandPagesTest.php tests/Feature/PublicFilamentAssetsTest.php tests/Feature/CampistaRegistrationPageTest.php
```

Expected: PASS or report unrelated pre-existing failures with evidence.

- [ ] **Step 3: Run formatter/checks**

Run:

```bash
vendor/bin/pint --dirty
git diff --check
```

Expected: no formatting errors and no whitespace errors.

- [ ] **Step 4: Confirm dependency and panel**

Run:

```bash
composer show leandrocfe/filament-apex-charts --locked
php artisan filament:about
```

Expected: Apex Charts package is installed and Filament panel boots.

## Self-Review

- Spec coverage: dashboard scope, Apex charts, global filters, operational-only finance boundary, health privacy, new roles, service extraction, and tests are covered.
- Placeholder scan: no implementation step depends on unspecified files or unknown behavior.
- Type consistency: all planned classes use `OperationalDashboardData` and Filament 5 namespace conventions already present in the repo.
