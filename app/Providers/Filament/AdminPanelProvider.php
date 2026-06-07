<?php

namespace App\Providers\Filament;

use App\Filament\Dashboard;
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
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
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
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Gestão Acampamento')
                    ->icon('iconpark-camp'),
                NavigationGroup::make()
                    ->label('Administrativo')
                    ->icon('heroicon-o-shield-check')
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
            ->favicon(asset('img/logo_simple.png'))
            ->defaultThemeMode(ThemeMode::Light)
            ->brandLogo(asset('img/logo_simple.png'))
            ->darkModeBrandLogo(asset('img/logo_simple.png'))
            ->brandLogoHeight('40px')
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
                EnvironmentIndicatorPlugin::make()
                    ->visible(fn () => auth()->user()?->hasRole('Super Administrador'))
                    ->color(fn () => match (app()->environment()) {
                        'production' => null,
                        'staging' => Color::Orange,
                        default => Color::Blue,
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
