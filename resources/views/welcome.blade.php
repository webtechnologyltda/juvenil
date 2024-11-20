<!DOCTYPE html>
<html lang="pt_BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{config('app.name')}}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo_light.png') }}">
    @filamentStyles
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
    <body class="font-primary bg-gray-950 scrollbar-thin scrollbar-track-gray-950 scrollbar-thumb-gray-800 scrollbar-thumb-rounded-full">
        @livewire('notifications')
        <main class="w-full">
                @include('components.navigation')
                @include('components.content-about-details')
                @include('components.content-form', ['settings' => $settings])
{{--                @include('components.content-testemunho')--}}
                @include('components.content-location_tk')
                @include('components.footer')
        </main>
        @filamentScripts
        @livewireScripts
        <script src="{{asset('js/jquery-min.js')}}"></script>
        <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    </body>
</html>
