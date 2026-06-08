<?php

namespace App\Providers\Filament;

use App\Filament\Dashboard;
use App\Filament\Pages\Auth\Login;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->colors([
                'primary' => Color::generatePalette('#f46b12'),
                'info' => Color::Cyan,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'violet' => Color::Violet,
                'gray' => Color::Gray,
                'orange' => Color::Orange,
                'teal' => Color::Teal,
                'pink' => Color::Pink,
                'blue' => Color::Blue,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->darkMode(isForced: true)
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Gestão Acampamento')
                    ->icon('iconpark-camp'),
                NavigationGroup::make()
                    ->label('Administrativo')
                    ->icon('heroicon-o-shield-check')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Financeiro')
                    ->icon('heroicon-o-banknotes')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Relatórios')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Configurações')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(false),

            ])
//            ->topNavigation()
            ->maxContentWidth(Width::ScreenTwoExtraLarge)
            ->favicon(asset('img/logo.png'))
            ->defaultThemeMode(ThemeMode::Dark)
            ->brandLogo(asset('img/logo.png'))
            ->darkModeBrandLogo(asset('img/logo.png'))
            ->brandLogoHeight('54px')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentApexChartsPlugin::make(),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        userMenuLabel: 'Meu perfil',
                        shouldRegisterNavigation: false,
                        slug: 'meu-perfil',
                    )
                    ->enableTwoFactorAuthentication(force: false)
                    ->enableBrowserSessions()
                    ->enablePasskeys(
                        relyingPartyIcon: '/img/logo.png',
                    ),
                EnvironmentIndicatorPlugin::make()
                    ->visible(fn () => auth()->user()?->hasRole('Super Administrador'))
                    ->color(fn () => match (app()->environment()) {
                        'production' => null,
                        'staging' => Color::generatePalette('#f46b12'),
                        default => Color::generatePalette('#f46b12'),
                    }),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s');
    }
}
