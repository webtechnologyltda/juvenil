<?php

use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;

it('renders the themed legal pages with the custom cookie consent banner', function (string $route, string $heading) {
    $this->withoutVite();

    $this->get(route($route))
        ->assertOk()
        ->assertSee($heading)
        ->assertSee('juvenil-legal-page')
        ->assertSee('img/logo.png')
        ->assertSee('hero-mobile.png')
        ->assertSee('hero-desktop.png')
        ->assertSee('js-cookie-consent')
        ->assertSee('juvenil_cookie_consent')
        ->assertSee('Cookies do Juvenil')
        ->assertDontSee('bg-pink-600')
        ->assertDontSee('fundo.jpg')
        ->assertDontSee('fonts.googleapis.com');
})->with([
    ['termos-inscricao', 'Termos de Inscrição'],
    ['politica-privacidade', 'Política de Privacidade'],
]);

it('renders the custom Filament login page for the admin panel', function () {
    $this->withoutVite();

    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('juvenil-admin-login')
        ->assertSee('juvenil-admin-ops-panel')
        ->assertSee('Entrar no painel')
        ->assertSee('Acampamento Juvenil')
        ->assertSee('Operação do evento')
        ->assertSee('Inscrições organizadas')
        ->assertSee('Pagamentos acompanhados')
        ->assertSee('hero-desktop.png')
        ->assertSee('js-cookie-consent')
        ->assertDontSee('Acompanhe inscrições, pagamentos e dados dos campistas em um painel alinhado ao tema do acampamento.')
        ->assertDontSee('Faça login');
});

it('configures the authenticated Filament panel with the Juvenil site theme', function () {
    $panel = Filament::getPanel('admin');
    $provider = file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php'));
    $adminCss = file_get_contents(resource_path('css/filament/admin/theme.css'));
    $campistasList = file_get_contents(app_path('Filament/Resources/CampistaResource/Pages/ListCampistas.php'));
    $legacyTableActionImports = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Filament'))))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile() && $file->getExtension() === 'php')
        ->filter(fn (SplFileInfo $file): bool => str_contains(file_get_contents($file->getPathname()), 'Filament\\Tables\\Actions'))
        ->map(fn (SplFileInfo $file): string => $file->getPathname())
        ->values()
        ->all();

    expect($panel->getDefaultThemeMode())
        ->toBe(ThemeMode::Dark)
        ->and($panel->hasDarkModeForced())
        ->toBeTrue()
        ->and($panel->getBrandLogo())
        ->toContain('img/logo.png')
        ->and($panel->getDarkModeBrandLogo())
        ->toContain('img/logo.png')
        ->and($panel->getFavicon())
        ->toContain('img/logo.png')
        ->and($provider)
        ->not->toContain('logo_simple.png')
        ->not->toContain('ThemeMode::Light')
        ->and($adminCss)
        ->toContain('body.fi-panel-admin:not(.juvenil-admin-auth-body)')
        ->toContain('--juvenil-camp-orange: #f46b12;')
        ->toContain('.fi-sidebar')
        ->toContain('.fi-topbar')
        ->toContain('.fi-ta')
        ->toContain('.fi-wi-stats-overview-stat')
        ->toContain('.fi-btn-color-primary')
        ->and($campistasList)
        ->toContain('use Filament\Schemas\Components\Tabs\Tab;')
        ->not->toContain('use Filament\Resources\Components\Tab;')
        ->and($legacyTableActionImports)
        ->toBeEmpty();
});

it('renders branded error pages', function () {
    $this->withoutVite();

    foreach ([
        403 => 'Acesso restrito',
        419 => 'Sessão expirada',
        500 => 'Algo saiu do planejado',
        503 => 'Sistema em manutenção',
    ] as $status => $heading) {
        $html = view("errors.{$status}")->render();

        expect($html)
            ->toContain((string) $status)
            ->toContain($heading)
            ->toContain('juvenil-poster-title')
            ->toContain('img/logo.png')
            ->not->toContain('bg-color1');
    }

    $this->get('/pagina-que-nao-existe')
        ->assertNotFound()
        ->assertSee('Página fora da trilha')
        ->assertSee('juvenil-poster-title')
        ->assertDontSee('bg-color1');
});

it('uses the customized Laravel cookie consent package', function () {
    $composer = file_get_contents(base_path('composer.json'));
    $config = config('cookie-consent');
    $dialog = file_get_contents(resource_path('views/vendor/cookie-consent/dialogContents.blade.php'));
    $publicCss = file_get_contents(resource_path('css/app.css'));
    $adminCss = file_get_contents(resource_path('css/filament/admin/theme.css'));
    $translation = trans('cookie-consent::texts.message');

    expect($composer)
        ->toContain('"spatie/laravel-cookie-consent": "^3.4"')
        ->and($config['cookie_name'])
        ->toBe('juvenil_cookie_consent')
        ->and($config['cookie_lifetime'])
        ->toBe(365)
        ->and($dialog)
        ->toContain('Cookies do Juvenil')
        ->toContain('z-40')
        ->not->toContain('z-[9998]')
        ->toContain("route('politica-privacidade')")
        ->toContain("route('termos-inscricao')")
        ->and($publicCss)
        ->toContain('body.has-mobile-bottom-nav .js-cookie-consent')
        ->and($adminCss)
        ->toContain("@source '../../../../resources/views/vendor/cookie-consent';")
        ->toContain('.juvenil-admin-auth-body .js-cookie-consent')
        ->and($translation)
        ->toContain('Acampamento Juvenil');
});
