<!DOCTYPE html>
<html lang="pt_BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{config('app.name')}}</title>
    <link rel="icon" type="image/webp" href="{{ asset('img/logo.webp') }}">
    <link rel="preload" as="image" href="{{ asset('img/hero-mobile.webp') }}" type="image/webp" media="(max-width: 1023px)">
    <link rel="preload" as="image" href="{{ asset('img/hero-desktop.webp') }}" type="image/webp" media="(min-width: 1024px)">
    @filamentStyles
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
    <body class="has-mobile-bottom-nav is-loading bg-[#052f35] font-primary text-white scrollbar-thin scrollbar-track-[#052f35] scrollbar-thumb-[#f46b12] scrollbar-thumb-rounded-full">
        <div class="juvenil-page-loader" data-campfire-loader role="status" aria-live="polite">
            <div class="juvenil-page-loader__panel">
                <img
                    src="{{ asset('img/campfire-loader.gif') }}"
                    alt=""
                    width="96"
                    height="96"
                    class="juvenil-page-loader__gif"
                    data-loader-flame
                >
                <p class="juvenil-page-loader__title">Acampamento Juvenil</p>
                <span class="juvenil-page-loader__text">Preparando a inscrição</span>
                <span class="campfire-loader-progress-track" aria-hidden="true">
                    <span class="campfire-loader-progress" data-loader-progress></span>
                </span>
            </div>
        </div>

        @livewire('notifications')
        <main class="w-full overflow-x-clip bg-[#052f35]">
                @include('components.navigation')
                @include('components.content-about-details', ['settings' => $settings])
                @include('components.content-form', ['settings' => $settings])
{{--                @include('components.content-testemunho')--}}
                @include('components.content-location_tk')
                @include('components.footer')
        </main>
        @include('components.mobile-bottom-nav')
        @filamentScripts
        @livewireScripts
    </body>
</html>
