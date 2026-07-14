<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    protected const SENSITIVE_HEALTH_PERMISSION = 'view_sensitive_health_campista';

    protected const REPORTS_PAGE_PERMISSION = 'page_reports_page';

    protected const TEAM_WORK_GROUPS_PAGE_PERMISSION = 'page_equipe_trabalho_groups';

    protected const REGISTRATION_FICHAS_REPORT_PERMISSION = 'print_registration_fichas_report';

    protected const TRIBE_QUADRANT_REPORT_PERMISSION = 'print_tribe_quadrant_report';

    protected const MISSION_CONTACTS_REPORT_PERMISSION = 'print_mission_contacts_report';

    protected const SENSITIVE_HEALTH_REPORT_PERMISSION = 'print_sensitive_health_report';

    protected const REGISTRATION_PAYMENTS_REPORT_PERMISSION = 'print_registration_payments_report';

    public function run(): void
    {
        static::syncRolesAndPermissions();

        $this->command?->info('Shield Seeding Completed.');
    }

    public static function syncRolesAndPermissions(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = static::getPermissionNames();

        static::makeSuperAdminWithPermissions($permissions);
        static::makeAdministratorWithPermissions($permissions);
        static::makeFinanceWithPermissions($permissions);
        static::makeInfirmaryWithPermissions($permissions);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    protected static function makeSuperAdminWithPermissions(array $permissions): void
    {
        $role = static::firstOrCreateRole(
            config('filament-shield.super_admin.name', 'Super Administrador'),
            RoleEnum::SuperAdministrador->value,
        );

        $role->syncPermissions(static::makePermissionModels($permissions));
    }

    protected static function makeAdministratorWithPermissions(array $permissions): void
    {
        $role = static::firstOrCreateRole(
            RoleEnum::getRoleEnumDescription(RoleEnum::Administrador),
            RoleEnum::Administrador->value,
        );

        $role->syncPermissions(static::makePermissionModels(
            static::administratorPermissionNames($permissions),
        ));
    }

    protected static function makeFinanceWithPermissions(array $permissions): void
    {
        $role = static::firstOrCreateRole(
            RoleEnum::getRoleEnumDescription(RoleEnum::Financeiro),
            RoleEnum::Financeiro->value,
        );

        $role->syncPermissions(static::makePermissionModels(
            static::financePermissionNames($permissions),
        ));
    }

    protected static function makeInfirmaryWithPermissions(array $permissions): void
    {
        $role = static::firstOrCreateRole(
            RoleEnum::getRoleEnumDescription(RoleEnum::Enfermaria),
            RoleEnum::Enfermaria->value,
        );

        $role->syncPermissions(static::makePermissionModels(
            static::infirmaryPermissionNames($permissions),
        ));
    }

    protected static function firstOrCreateRole(string $name, int $id)
    {
        /** @var class-string<Role> $roleModel */
        $roleModel = Utils::getRoleModel();

        $guard = 'web';

        $role = $roleModel::query()
            ->where('name', $name)
            ->where('guard_name', $guard)
            ->first();

        if ($role !== null) {
            return $role;
        }

        $role = new $roleModel;
        $role->forceFill([
            'id' => $id,
            'name' => $name,
            'guard_name' => $guard,
        ]);
        $role->save();

        return $role;
    }

    protected static function makePermissionModels(array $permissions): array
    {
        /** @var class-string<Permission> $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        $guard = 'web';

        return collect($permissions)
            ->map(fn (string $permission) => $permissionModel::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]))
            ->all();
    }

    protected static function administratorPermissionNames(array $permissions): array
    {
        return collect($permissions)
            ->filter(fn (string $permission): bool => str_ends_with($permission, '_campista')
                || str_ends_with($permission, '_tribo')
                || str_contains($permission, 'dashboard')
                || str_contains($permission, 'operational'))
            ->reject(fn (string $permission): bool => $permission === self::SENSITIVE_HEALTH_PERMISSION
                || str_contains($permission, 'financeiro')
                || str_contains($permission, 'lancamento'))
            ->merge([
                self::TEAM_WORK_GROUPS_PAGE_PERMISSION,
            ])
            ->merge(static::administratorReportPermissionNames())
            ->unique()
            ->values()
            ->all();
    }

    protected static function financePermissionNames(array $permissions): array
    {
        return collect($permissions)
            ->filter(fn (string $permission): bool => str_contains($permission, 'financial')
                || str_contains($permission, 'financeiro')
                || str_contains($permission, 'lancamento'))
            ->reject(fn (string $permission): bool => $permission === self::SENSITIVE_HEALTH_PERMISSION
                || str_contains($permission, 'operational')
                || str_contains($permission, 'campista')
                || str_contains($permission, 'tribo'))
            ->merge([
                self::REGISTRATION_PAYMENTS_REPORT_PERMISSION,
            ])
            ->values()
            ->all();
    }

    protected static function infirmaryPermissionNames(array $permissions): array
    {
        return collect($permissions)
            ->filter(fn (string $permission): bool => in_array($permission, [
                'view_any_campista',
                'view_campista',
                self::SENSITIVE_HEALTH_PERMISSION,
            ], true))
            ->merge([
                self::REPORTS_PAGE_PERMISSION,
                self::SENSITIVE_HEALTH_REPORT_PERMISSION,
            ])
            ->unique()
            ->values()
            ->all();
    }

    protected static function administratorReportPermissionNames(): array
    {
        return [
            self::REPORTS_PAGE_PERMISSION,
            self::REGISTRATION_FICHAS_REPORT_PERMISSION,
            self::TRIBE_QUADRANT_REPORT_PERMISSION,
            self::MISSION_CONTACTS_REPORT_PERMISSION,
        ];
    }

    protected static function getPermissionNames(): array
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return collect()
            ->merge(static::resourcePermissionNames())
            ->merge(static::pagePermissionNames())
            ->merge(static::widgetPermissionNames())
            ->merge(static::customPermissionNames())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    protected static function resourcePermissionNames(): Collection
    {
        return collect(FilamentShield::getResources())
            ->flatMap(fn (array $resource): array => collect($resource['permissions'] ?? [])
                ->pluck('key')
                ->all());
    }

    protected static function pagePermissionNames(): Collection
    {
        return collect(FilamentShield::getPages())
            ->flatMap(fn (array $page): array => array_keys($page['permissions'] ?? []));
    }

    protected static function widgetPermissionNames(): Collection
    {
        return collect(FilamentShield::getWidgets())
            ->flatMap(fn (array $widget): array => array_keys($widget['permissions'] ?? []));
    }

    protected static function customPermissionNames(): array
    {
        return collect(config('filament-shield.custom_permissions', []))
            ->keys()
            ->all();
    }
}
