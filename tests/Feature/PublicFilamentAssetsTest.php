<?php

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;

it('loads the Filament component styles required by the public registration form', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('@layer theme, base, legacy, components, utilities;')
        ->toContain("@import './output.css' layer(legacy);")
        ->toContain("../../vendor/filament/support/resources/css/index.css")
        ->toContain("../../vendor/filament/actions/resources/css/index.css")
        ->toContain("../../vendor/filament/forms/resources/css/index.css")
        ->toContain("../../vendor/filament/notifications/resources/css/index.css")
        ->toContain("../../vendor/filament/schemas/resources/css/index.css");
});

it('uses the artwork orange as the Filament primary color', function () {
    expect(FilamentColor::getColor('primary'))
        ->toBe(Color::generatePalette('#f46b12'));
});

it('ships the public GSAP motion layer and custom loader asset', function () {
    $js = file_get_contents(resource_path('js/app.js'));
    $css = file_get_contents(resource_path('css/app.css'));

    expect($js)
        ->toContain('ScrollToPlugin')
        ->toContain('isMobileViewport')
        ->toContain('initCampfireLoader')
        ->toContain('initMobileBottomNav')
        ->toContain('initExperienceParallax')
        ->toContain('data-motion-card')
        ->toContain('data-anchor-scroll')
        ->toContain('data-mobile-nav-item')
        ->toContain('gsap-scrolling')
        ->toContain('scrollToTarget')
        ->and($css)
        ->toContain('.juvenil-page-loader')
        ->toContain('.juvenil-mobile-bottom-nav')
        ->toContain('env(safe-area-inset-bottom)')
        ->toContain('@media (min-width: 1024px)')
        ->toContain('.campfire-loader-progress')
        ->toContain('html.gsap-scrolling')
        ->and(public_path('img/campfire-loader.gif'))
        ->toBeFile();
});

it('uses responsive hero artwork and the unified autoplay camp experience section', function () {
    $navigation = file_get_contents(resource_path('views/components/navigation.blade.php'));
    $banner = file_get_contents(resource_path('views/components/home-banner.blade.php'));
    $details = file_get_contents(resource_path('views/components/content-about-details.blade.php'));
    $css = file_get_contents(resource_path('css/app.css'));
    $js = file_get_contents(resource_path('js/app.js'));

    expect($navigation)
        ->toContain('juvenil-hero-backdrop')
        ->toContain('hero-mobile.png')
        ->toContain('hero-desktop.png')
        ->toContain('hidden w-[calc(100%-2rem)]')
        ->toContain('lg:flex')
        ->not->toContain('acampamento-juvenil-divulgacao')
        ->and($banner)
        ->toContain('sr-only')
        ->toContain('min-h-[100dvh]')
        ->not->toContain('acampamento-juvenil-divulgacao')
        ->and($details)
        ->toContain('juvenil-experience-section')
        ->toContain('juvenil-experience-video')
        ->toContain('juvenil-experience-copy')
        ->toContain('barraca.mp4')
        ->toContain('autoplay')
        ->toContain('muted')
        ->toContain('loop')
        ->toContain('playsinline')
        ->not->toContain('juvenil-bento-grid')
        ->not->toContain('controls')
        ->and($css)
        ->toContain('.juvenil-hero-backdrop')
        ->toContain('var(--juvenil-hero-mobile)')
        ->toContain('var(--juvenil-hero-desktop)')
        ->toContain('.juvenil-experience-section')
        ->toContain('.juvenil-experience-video')
        ->toContain('.juvenil-site-video')
        ->not->toContain('.juvenil-bento-grid')
        ->and($js)
        ->toContain('initExperienceParallax')
        ->not->toContain('juvenil-bento-grid')
        ->and(public_path('img/hero-mobile.png'))
        ->toBeFile()
        ->and(public_path('img/hero-desktop.png'))
        ->toBeFile()
        ->and(public_path('img/barraca.mp4'))
        ->toBeFile();
});
