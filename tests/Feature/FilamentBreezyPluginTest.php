<?php

it('installs and registers Breezy on the admin Filament panel', function () {
    $composer = file_get_contents(base_path('composer.json'));
    $adminPanelProvider = file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php'));
    $theme = file_get_contents(resource_path('css/filament/admin/theme.css'));
    $userModel = file_get_contents(app_path('Models/User.php'));
    $environmentExample = file_get_contents(base_path('.env.example'));
    $breezySessionMigrations = glob(database_path('migrations/*_create_breezy_sessions_table.php'));
    $passkeyMigrations = glob(database_path('migrations/*_create_passkeys_table.php'));

    expect($composer)
        ->toContain('"jeffgreco13/filament-breezy"')
        ->and($adminPanelProvider)
        ->toContain('use Jeffgreco13\FilamentBreezy\BreezyCore;')
        ->toContain('BreezyCore::make()')
        ->toContain('->myProfile(')
        ->toContain("userMenuLabel: 'Meu perfil'")
        ->toContain("slug: 'meu-perfil'")
        ->toContain('->enableTwoFactorAuthentication(force: false)')
        ->toContain('->enableBrowserSessions()')
        ->toContain('->enablePasskeys(')
        ->and($theme)
        ->toContain("@source '../../../../vendor/jeffgreco13/filament-breezy/resources/**/*';")
        ->and($userModel)
        ->toContain('use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;')
        ->toContain('use TwoFactorAuthenticatable;')
        ->and($environmentExample)
        ->toContain('SESSION_DRIVER=database')
        ->and($breezySessionMigrations)
        ->not->toBeEmpty()
        ->and($passkeyMigrations)
        ->not->toBeEmpty();
});

it('translates Breezy browser sessions and passkeys profile labels in Portuguese', function () {
    app()->setLocale('pt_BR');

    expect(__('filament-breezy::default.profile.browser_sessions.heading'))
        ->toBe('Sessões de Navegador')
        ->and(__('filament-breezy::default.profile.passkeys.heading'))
        ->toBe('Chaves de acesso')
        ->and(__('filament-breezy::default.fields.passkey_name'))
        ->toBe('Nome da chave de acesso')
        ->and(__('filament-breezy::default.fields.last_used_at'))
        ->toBe('Último uso');
});
