<?php

declare(strict_types=1);

use App\Filament\Resources\CampistaResource;
use App\Filament\Resources\EquipeTrabalhoResource;
use App\Filament\Resources\TriboResource;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

return [
    'shield_resource' => [
        'slug' => 'roles',
        'show_model_path' => true,
        'cluster' => null,
        'tabs' => [
            'pages' => true,
            'widgets' => true,
            'resources' => true,
            'custom_permissions' => true,
        ],
    ],

    'tenant_model' => null,

    'auth_provider_model' => 'App\\Models\\User',

    'super_admin' => [
        'enabled' => true,
        'name' => 'Super Administrador',
        'define_via_gate' => false,
        'intercept_gate' => 'before',
    ],

    'panel_user' => [
        'enabled' => true,
        'name' => 'Usuário Comum',
    ],

    'permissions' => [
        'separator' => '_',
        'case' => 'snake',
        'generate' => true,
    ],

    'policies' => [
        'path' => app_path('Policies'),
        'merge' => true,
        'generate' => true,
        'methods' => [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'deleteAny',
            'restore',
            'forceDelete',
            'forceDeleteAny',
            'restoreAny',
            'replicate',
            'reorder',
        ],
        'single_parameter_methods' => [
            'viewAny',
            'create',
            'deleteAny',
            'forceDeleteAny',
            'restoreAny',
            'reorder',
        ],
    ],

    'localization' => [
        'enabled' => false,
        'key' => 'filament-shield::filament-shield.resource_permission_prefixes_labels',
    ],

    'resources' => [
        'subject' => 'model',
        'manage' => [
            CampistaResource::class => [
                'audit',
                'export',
                'restoreAudit',
                'updateTribo',
                'viewSensitiveHealth',
            ],
            EquipeTrabalhoResource::class => [
                'export',
            ],
            RoleResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
            ],
            TriboResource::class => [
                'audit',
                'restoreAudit',
            ],
        ],
        'exclude' => [],
    ],

    'pages' => [
        'subject' => 'class',
        'prefix' => 'page',
        'exclude' => [
            Dashboard::class,
        ],
    ],

    'widgets' => [
        'subject' => 'class',
        'prefix' => 'widget',
        'exclude' => [
            AccountWidget::class,
            FilamentInfoWidget::class,
        ],
    ],

    'custom_permissions' => [
        'print_mission_contacts_report' => 'Imprimir relatório de contatos e endereços',
        'print_registration_payments_report' => 'Imprimir relatório de pagamentos de inscrições',
        'print_registration_fichas_report' => 'Imprimir fichas de inscrição',
        'print_sensitive_health_report' => 'Imprimir relatório médico da enfermaria',
        'print_tribe_quadrant_report' => 'Imprimir quadrante por tribo',
    ],

    'discovery' => [
        'discover_all_resources' => false,
        'discover_all_widgets' => false,
        'discover_all_pages' => false,
    ],

    'register_role_policy' => true,
];
