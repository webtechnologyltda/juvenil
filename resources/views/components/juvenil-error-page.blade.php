@props([
    'code',
    'title',
    'message',
    'actionLabel' => 'Voltar para inscrições',
    'image' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#03181c] font-primary text-white">
    <main class="relative isolate grid min-h-[100dvh] overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
        <div class="absolute inset-0 -z-30 bg-[url('/img/hero-mobile.png')] bg-cover bg-center opacity-45 lg:bg-[url('/img/hero-desktop.png')]" aria-hidden="true"></div>
        @if ($image)
            <img src="{{ asset($image) }}" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover opacity-18 mix-blend-screen">
        @endif
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_50%_18%,rgba(244,107,18,0.20),transparent_28rem),linear-gradient(180deg,rgba(5,47,53,0.84)_0%,#03181c_74%)]" aria-hidden="true"></div>

        <section class="mx-auto grid w-full max-w-6xl items-center gap-8 lg:grid-cols-[minmax(0,0.84fr)_minmax(0,1fr)]">
            <div class="hidden lg:block">
                <img
                    src="{{ asset('img/logo.png') }}"
                    alt="Logo do Acampamento Juvenil"
                    width="320"
                    height="320"
                    class="w-full max-w-xs object-contain drop-shadow-[0_24px_70px_rgba(0,0,0,0.42)]"
                >
            </div>

            <div class="border border-[#9ddbef]/22 bg-[#052f35]/84 p-6 shadow-[0_34px_110px_rgba(0,0,0,0.42)] backdrop-blur-xl sm:p-9 lg:p-12">
                <p class="juvenil-poster-title text-7xl uppercase leading-none text-[#f46b12] sm:text-8xl">{{ $code }}</p>
                <h1 class="mt-5 max-w-2xl text-4xl font-black uppercase leading-[0.95] text-white sm:text-5xl">{{ $title }}</h1>
                <p class="mt-5 max-w-xl text-base leading-7 text-[#d8f2fa]">{{ $message }}</p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('campista') }}" class="inline-flex min-h-12 items-center justify-center bg-[#f46b12] px-6 text-center text-sm font-black uppercase tracking-[0.14em] text-[#052f35] transition-colors hover:bg-[#ff8a2a]">
                        {{ $actionLabel }}
                    </a>
                    <a href="{{ route('campista') }}#registration" class="inline-flex min-h-12 items-center justify-center border border-[#9ddbef]/35 px-6 text-center text-sm font-black uppercase tracking-[0.14em] text-white transition-colors hover:border-[#f46b12] hover:text-[#f46b12]">
                        Inscrição
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
