<?php

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;

it('loads the Filament component styles required by the public registration form', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('@layer theme, base, legacy, components, utilities;')
        ->toContain("@import './output.css' layer(legacy);")
        ->toContain('../../vendor/filament/support/resources/css/index.css')
        ->toContain('../../vendor/filament/actions/resources/css/index.css')
        ->toContain('../../vendor/filament/forms/resources/css/index.css')
        ->toContain('../../vendor/filament/infolists/resources/css/index.css')
        ->toContain('../../vendor/filament/notifications/resources/css/index.css')
        ->toContain('../../vendor/filament/schemas/resources/css/index.css');
});

it('keeps Filament modals viewport scoped across public registration surfaces', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('.fi-modal {')
        ->toContain('position: fixed;')
        ->toContain('inset: 0;')
        ->toContain('z-index: 1200;')
        ->toContain('.fi-modal > .fi-modal-close-overlay {')
        ->toContain('background: rgba(3, 24, 28, 0.72);')
        ->toContain('backdrop-filter: blur(6px);')
        ->toContain('.fi-modal > .fi-modal-window-ctn {')
        ->toContain('min-height: 100dvh;')
        ->toContain('grid-template-rows: minmax(1rem, 1fr) auto minmax(1rem, 1fr);')
        ->toContain('body:has(.fi-modal.fi-modal-open) :is([data-motion-card], .out, .filament-registration-shell, .fi-section, .fi-ta, .fi-wi-stats-overview-stat, .fi-in-entry-wrp) {')
        ->toContain('transform: none !important;')
        ->toContain('backdrop-filter: none;');
});

it('lets Filament table column dropdowns escape table clipping in shared styles', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('.fi-dropdown-panel {')
        ->toContain('z-index: 100;')
        ->toContain('.fi-ta {')
        ->toContain('overflow: visible;')
        ->toContain('.fi-ta:has(.fi-dropdown-panel)')
        ->toContain('z-index: 40;')
        ->toContain('.fi-ta .fi-dropdown-panel')
        ->toContain('z-index: 100;')
        ->toContain('.fi-ta-col-manager-dropdown > .fi-dropdown-panel')
        ->toContain('max-height: min(34rem, calc(100dvh - 2rem));')
        ->toContain('overflow-y: auto;')
        ->toContain('overscroll-behavior: contain;');
});

it('uses the artwork orange as the Filament primary color', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    $tailwindConfig = file_get_contents(base_path('tailwind.config.js'));

    expect(FilamentColor::getColor('primary'))
        ->toBe(Color::generatePalette('#f46b12'))
        ->and($css)
        ->toContain('--primary-500: oklch(0.68270588235294 0.17009090909091 45.756);')
        ->toContain(".filament-registration-shell :is(input[type='checkbox'], input[type='radio'])")
        ->toContain('.filament-registration-shell .fi-color-primary')
        ->and($tailwindConfig)
        ->toContain('primary: {')
        ->toContain("500: 'oklch(0.68270588235294 0.17009090909091 45.756)'")
        ->not->toContain('colors.yellow');
});

it('uses toggle buttons and editable automatic square image uploads for campista registration photos', function () {
    $campistaRegistrationForm = file_get_contents(app_path('Livewire/CampistaForm.php'));
    $forms = [
        $campistaRegistrationForm,
        file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaForm.php')),
        file_get_contents(app_path('Filament/Resources/EquipeTrabalhoResource/EquipeTrabalhoForm.php')),
    ];

    foreach ($forms as $form) {
        expect($form)
            ->toContain("FileUpload::make('avatar_url')")
            ->toContain("'image/jpeg'")
            ->toContain("'image/png'")
            ->toContain("'image/webp'")
            ->toContain("->rules(['mimes:jpg,jpeg,png,webp'])")
            ->toContain("->imageAspectRatio('1:1')")
            ->toContain('->automaticallyCropImagesToAspectRatio()')
            ->toContain("->automaticallyResizeImagesMode('cover')")
            ->toContain("->automaticallyResizeImagesToWidth('500')")
            ->toContain("->automaticallyResizeImagesToHeight('500')")
            ->toContain('->automaticallyUpscaleImagesWhenResizing(false)')
            ->toContain("->panelAspectRatio('1:1')")
            ->not->toContain('->imageEditorAspectRatios')
            ->not->toContain('->imageEditorEmptyFillColor')
            ->not->toContain('->imageCropAspectRatio')
            ->not->toContain('->orientImagesFromExif(false)');
    }

    expect($campistaRegistrationForm)
        ->toContain('->imageEditor()')
        ->toContain("->imageEditorAspectRatioOptions(['1:1'])")
        ->toContain('->automaticallyOpenImageEditorForAspectRatio()');

    foreach (array_slice($forms, 0, 2) as $form) {
        expect($form)
            ->toContain('use Filament\Forms\Components\ToggleButtons;')
            ->toContain('use Filament\Support\Colors\Color;')
            ->toContain("ToggleButtons::make('form_data.sexo')")
            ->toContain("'M' => Color::Blue")
            ->toContain("'F' => Color::Pink")
            ->not->toContain('use Filament\Forms\Components\Radio;')
            ->not->toContain('Radio::make');
    }
});

it('stores registration photo uploads on the public disk so previews can load saved images', function () {
    $campistaForm = file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaForm.php'));
    $equipeForm = file_get_contents(app_path('Filament/Resources/EquipeTrabalhoResource/EquipeTrabalhoForm.php'));
    $campistaTable = file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaTable.php'));
    $equipeTable = file_get_contents(app_path('Filament/Resources/EquipeTrabalhoResource/EquipeTrabalhoTable.php'));
    $campistaView = file_get_contents(app_path('Filament/Resources/CampistaResource/Pages/ViewCampista.php'));
    $publicCampistaForm = file_get_contents(app_path('Livewire/CampistaForm.php'));
    $publicEquipeForm = file_get_contents(app_path('Livewire/EquipeTrabalhoForm.php'));

    expect($campistaForm)
        ->toContain("FileUpload::make('avatar_url')")
        ->toContain("->disk('public')")
        ->toContain("->directory('foto-formulario')")
        ->toContain('Storage::disk(\'public\')->url($record->avatar_url)')
        ->and($equipeForm)
        ->toContain("FileUpload::make('avatar_url')")
        ->toContain("->disk('public')")
        ->toContain("->directory('foto-formulario-equipe-trabalho')")
        ->toContain('Storage::disk(\'public\')->url($record->avatar_url)')
        ->and($campistaTable)
        ->toContain("ImageColumn::make('avatar_url')")
        ->toContain("->disk('public')")
        ->and($equipeTable)
        ->toContain("ImageColumn::make('avatar_url')")
        ->toContain("->disk('public')")
        ->and($campistaView)
        ->toContain('Storage::disk(\'public\')->url($avatar)')
        ->and($publicCampistaForm)
        ->toContain('FilamentUploadState::storedPath($this->data[\'avatar_url\'] ?? null, \'foto-formulario\')')
        ->and($publicEquipeForm)
        ->toContain('FilamentUploadState::storedPath($this->data[\'avatar_url\'] ?? null, \'foto-formulario-equipe-trabalho\')');
});

it('keeps parish community fields visible for zero-valued parish options', function () {
    $forms = [
        file_get_contents(app_path('Livewire/CampistaForm.php')),
        file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaForm.php')),
    ];

    foreach ($forms as $form) {
        expect($form)
            ->toContain('selectedParishIs($get, 0)')
            ->toContain('selectedParishIs($get, 1)')
            ->toContain('selectedParishIs($get, 2)')
            ->not->toContain("\$get('form_data.paroquia') != null");
    }
});

it('renders address hints without technical placeholder labels on public registration forms', function () {
    $forms = [
        file_get_contents(app_path('Livewire/CampistaForm.php')),
        file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaForm.php')),
        file_get_contents(app_path('Filament/Resources/EquipeTrabalhoResource/EquipeTrabalhoForm.php')),
    ];

    foreach ($forms as $form) {
        expect($form)
            ->toContain('Html::make(new HtmlString(')
            ->not->toContain("Placeholder::make('info_endereco')");
    }

    expect(file_get_contents(app_path('Livewire/CampistaForm.php')))
        ->toContain("Placeholder::make('mensagem_foto')")
        ->toContain("Placeholder::make('info_termo')");
});

it('labels address complement fields without point of reference wording', function () {
    $files = [
        file_get_contents(app_path('Livewire/CampistaForm.php')),
        file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaForm.php')),
        file_get_contents(app_path('Filament/Resources/EquipeTrabalhoResource/EquipeTrabalhoForm.php')),
        file_get_contents(app_path('Filament/Resources/CampistaResource/Pages/ViewCampista.php')),
        file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaExport.php')),
        file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaTable.php')),
        file_get_contents(app_path('Filament/Exports/EquipeTrabalhoExporter.php')),
    ];

    foreach ($files as $file) {
        expect($file)
            ->toContain('Complemento')
            ->not->toContain('Ponto Referência')
            ->not->toContain('Ponto de referência')
            ->not->toContain('Ponto Referencia');
    }
});

it('requires only one external responsible contact for campista registrations', function () {
    $publicForm = file_get_contents(app_path('Livewire/CampistaForm.php'));
    $adminForm = file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaForm.php'));
    $viewPage = file_get_contents(app_path('Filament/Resources/CampistaResource/Pages/ViewCampista.php'));
    $export = file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaExport.php'));
    $table = file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaTable.php'));
    $demoData = file_get_contents(database_path('seeders/Support/DemoRegistrationData.php'));

    foreach ([$publicForm, $adminForm, $viewPage, $demoData] as $file) {
        expect($file)
            ->toContain('telefone_reponsavel_1')
            ->toContain('telefone_reponsavel_nome_1')
            ->not->toContain('telefone_reponsavel_2')
            ->not->toContain('telefone_reponsavel_nome_2');
    }

    expect($publicForm)
        ->toContain('uma pessoa responsável')
        ->not->toContain('duas pessoas responsáveis')
        ->and($viewPage)
        ->not->toContain("'Contato 2'")
        ->not->toContain("'Telefone 2'")
        ->and($export)
        ->toContain("ExportColumn::make('form_data.telefone_reponsavel_1')")
        ->not->toContain("ExportColumn::make('form_data.telefone_reponsavel')")
        ->and($table)
        ->toContain("TextColumn::make('form_data.telefone_reponsavel_1')")
        ->not->toContain("TextColumn::make('form_data.telefone_reponsavel')");
});

it('keeps the public photo upload compact without cropper modal overrides', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    $js = file_get_contents(resource_path('js/app.js'));

    expect($css)
        ->toContain('.filament-registration-shell .fi-fo-file-upload .filepond--root')
        ->toContain('width: min(100%, 18rem);')
        ->toContain('.filament-registration-shell .fi-fo-file-upload .filepond--drop-label')
        ->not->toContain('.fi-fo-file-upload-editor-window')
        ->not->toContain('.fi-fo-file-upload-editor-image-ctn')
        ->not->toContain('.fi-fo-file-upload-editor .cropper-container')
        ->toContain('[data-motion-card]:not(.filament-registration-shell)')
        ->toContain('.filament-registration-shell')
        ->toContain('will-change: auto;')
        ->and($js)
        ->toContain("element.style.willChange = 'auto';");
});

it('ships the public GSAP motion layer and custom loader asset', function () {
    $js = file_get_contents(resource_path('js/app.js'));
    $css = file_get_contents(resource_path('css/app.css'));
    $loader = imagecreatefromgif(public_path('img/campfire-loader.gif'));

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
        ->toBeFile()
        ->and(imagecolortransparent($loader))
        ->not->toBe(-1)
        ->and(imagecolorat($loader, 0, 0))
        ->toBe(imagecolortransparent($loader));

    imagedestroy($loader);
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
        ->toContain('order-2 mt-5 text-center')
        ->toContain('juvenil-poster-title order-1')
        ->not->toContain('sm:order-1')
        ->not->toContain('sm:order-2')
        ->not->toContain('acampamento-juvenil-divulgacao')
        ->and($details)
        ->toContain('juvenil-experience-section')
        ->toContain('juvenil-experience-video')
        ->toContain('juvenil-experience-copy')
        ->toContain('lg:pr-20')
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
