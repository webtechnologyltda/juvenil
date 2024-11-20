<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Columns
    |--------------------------------------------------------------------------
    */

    'column.name' => 'Nome',
    'column.guard_name' => 'Guard',
    'column.roles' => 'Grupo de Permissões',
    'column.permissions' => 'Permissões',
    'column.updated_at' => 'Alterado em',

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */

    'field.name' => 'Nome',
    'field.guard_name' => 'Guard',
    'field.permissions' => 'Permissões',
    'field.select_all.name' => 'Selecionar todos',
    'field.select_all.message' => 'Habilitar todas as permissões para essa função',

    /*
    |--------------------------------------------------------------------------
    | Navigation & Resource
    |--------------------------------------------------------------------------
    */

    'nav.group' => 'Administrativo',
    'nav.role.label' => 'Grupos de Permissões',
    'nav.role.icon' => 'heroicon-o-shield-check',
    'resource.label.role' => 'Grupo de Permissão',
    'resource.label.roles' => 'Grupos de Permissões',

    /*
    |--------------------------------------------------------------------------
    | Section & Tabs
    |--------------------------------------------------------------------------
    */
    'section' => 'Entidades',
    'resources' => 'Recursos',
    'widgets' => 'Widgets',
    'pages' => 'Páginas',
    'custom' => 'Permissões customizadas',

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    'forbidden' => 'Você não tem permissão para acessar',

    /*
    |--------------------------------------------------------------------------
    | Resource Permissions' Labels
    |--------------------------------------------------------------------------
    */

    'resource_permission_prefixes_labels' => [
        'view' => 'Visualizar',
        'view_any' => 'Visualizar lista',
        'create' => 'Criar',
        'update' => 'Atualizar',
        'delete' => 'Deletar',
        'delete_any' => 'Deletar vários',
        'force_delete' => 'Forçar Deleção',
        'force_delete_any' => 'Forçar Deleção de Vários',
        'restore' => 'Restaurar',
        'reorder' => 'Reordenar',
        'restore_any' => 'Restaurar Vários',
        'replicate' => 'Replicar',
        'audit' => 'Visualizar Auditoria',
        'restoreAudit' => 'Restaurar Auditoria',
        'export' => 'Exportar',
        'updateTribo' => 'Atualizar Tribo',
    ],
];
