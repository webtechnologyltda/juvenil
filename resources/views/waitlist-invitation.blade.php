<!DOCTYPE html>
<html lang="pt_BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convite da fila de espera - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">
    @filamentStyles
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#052f35] font-primary text-white scrollbar-thin scrollbar-track-[#052f35] scrollbar-thumb-[#f46b12] scrollbar-thumb-rounded-full">
    @livewire('notifications')

    <main class="min-h-screen bg-[#052f35] px-4 py-10 text-white">
        <section class="mx-auto max-w-5xl">
            <div class="mb-8 border border-[#9ddbef]/25 bg-[#03272c]/80 p-5">
                <p class="text-sm font-black uppercase tracking-[0.16em] text-[#f46b12]">Convite da fila de espera</p>
                <h1 class="mt-3 text-3xl font-black uppercase tracking-normal text-white sm:text-5xl">Complete sua inscrição</h1>
                <p class="mt-3 max-w-2xl text-sm font-semibold leading-7 text-[#d8f2fa]">
                    Esta ficha foi liberada por um link único enviado pela organização. Preencha os dados restantes para concluir sua inscrição.
                </p>
            </div>

            @livewire('campista-form', [
                'waitlistEntry' => $waitlistEntry,
                'token' => $token,
            ])
        </section>
    </main>

    @filamentScripts
    @livewireScripts
</body>
</html>
