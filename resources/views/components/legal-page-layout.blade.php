@props([
    'title',
    'eyebrow' => 'Documentos do Acampamento',
    'summary' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - {{ config('app.name') }}</title>
    <link rel="icon" type="image/webp" href="{{ asset('img/logo.webp') }}">
    <link rel="preload" as="image" href="{{ asset('img/hero-mobile.webp') }}" type="image/webp" media="(max-width: 1023px)">
    <link rel="preload" as="image" href="{{ asset('img/hero-desktop.webp') }}" type="image/webp" media="(min-width: 1024px)">
    @vite('resources/css/app.css')
</head>
<body class="bg-[#052f35] font-primary text-white scrollbar-thin scrollbar-track-[#052f35] scrollbar-thumb-[#f46b12] scrollbar-thumb-rounded-full">
    <main class="juvenil-legal-page relative isolate min-h-[100dvh] overflow-x-clip">
        <div class="absolute inset-0 -z-30 bg-[url('/img/hero-mobile.webp')] bg-cover bg-center opacity-50 lg:bg-[url('/img/hero-desktop.webp')]" aria-hidden="true"></div>
        <div class="absolute inset-0 -z-20 bg-[radial-gradient(circle_at_50%_8%,rgba(157,219,239,0.28),transparent_30rem),linear-gradient(180deg,rgba(5,47,53,0.76)_0%,#052f35_46%,#03181c_100%)]" aria-hidden="true"></div>

        <header class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
            <a href="{{ route('campista') }}" class="inline-flex min-w-0 items-center gap-3 border border-[#9ddbef]/20 bg-[#052f35]/82 px-3 py-2 backdrop-blur-xl">
                <img
                    src="{{ asset('img/logo.webp') }}"
                    alt="Logo do Acampamento Juvenil"
                    width="48"
                    height="48"
                    class="size-11 shrink-0 object-contain"
                >
                <span class="min-w-0">
                    <span class="block truncate text-xs font-black uppercase tracking-[0.22em] text-white">Juvenil</span>
                    <span class="block text-[0.62rem] font-black uppercase tracking-[0.18em] text-[#9ddbef]">22 a 26 de Julho</span>
                </span>
            </a>

            <a href="{{ route('campista') }}" class="hidden min-h-11 items-center justify-center border border-[#9ddbef]/30 px-5 text-xs font-black uppercase tracking-[0.16em] text-white transition-colors hover:border-[#f46b12] hover:text-[#f46b12] sm:inline-flex">
                Voltar ao site
            </a>
        </header>

        <section class="mx-auto grid w-full max-w-7xl gap-8 px-4 pb-20 pt-8 sm:px-6 lg:grid-cols-[minmax(0,0.76fr)_minmax(0,1.24fr)] lg:px-8 lg:pb-28 lg:pt-14">
            <aside class="lg:sticky lg:top-10 lg:self-start">
                <p class="text-xs font-black uppercase tracking-[0.28em] text-[#f46b12]">{{ $eyebrow }}</p>
                <h1 class="mt-4 text-4xl font-black uppercase leading-[0.94] text-white sm:text-5xl lg:text-6xl">{{ $title }}</h1>
                @if ($summary)
                    <p class="mt-6 max-w-xl text-base leading-7 text-[#d8f2fa]">{{ $summary }}</p>
                @endif

                <div class="mt-8 grid gap-3 sm:max-w-sm">
                    <a href="{{ route('campista') }}#registration" class="inline-flex min-h-12 items-center justify-center bg-[#f46b12] px-5 text-center text-sm font-black uppercase tracking-[0.14em] text-[#052f35] transition-colors hover:bg-[#ff8a2a]">
                        Ir para inscrição
                    </a>
                    <a href="{{ route('politica-privacidade') }}" class="inline-flex min-h-12 items-center justify-center border border-[#9ddbef]/35 px-5 text-center text-sm font-black uppercase tracking-[0.14em] text-[#d8f2fa] transition-colors hover:border-[#f46b12] hover:text-[#f46b12]">
                        Privacidade
                    </a>
                    <a href="{{ route('termos-inscricao') }}" class="inline-flex min-h-12 items-center justify-center border border-[#9ddbef]/35 px-5 text-center text-sm font-black uppercase tracking-[0.14em] text-[#d8f2fa] transition-colors hover:border-[#f46b12] hover:text-[#f46b12]">
                        Termos
                    </a>
                </div>
            </aside>

            <article class="juvenil-legal-content border border-[#9ddbef]/22 bg-white px-5 py-7 text-[#052f35] shadow-[0_34px_110px_rgba(0,0,0,0.35)] sm:px-8 sm:py-9 lg:px-10 lg:py-11">
                {{ $slot }}
            </article>
        </section>
    </main>
</body>
</html>
