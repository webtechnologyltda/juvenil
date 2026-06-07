<section
    id="top"
    class="juvenil-hero-shell relative isolate min-h-[100dvh] w-full overflow-hidden bg-[#052f35] text-white"
    style="--juvenil-hero-mobile: url('{{ asset('img/hero-mobile.png') }}'); --juvenil-hero-desktop: url('{{ asset('img/hero-desktop.png') }}');"
>
    <div class="juvenil-hero-backdrop absolute inset-0 -z-30" aria-hidden="true"></div>
    <div
        class="absolute inset-0 -z-20"
        style="background: radial-gradient(circle at 50% 18%, rgba(157, 219, 239, 0.34), transparent 34%), linear-gradient(180deg, rgba(8, 74, 84, 0.46) 0%, rgba(5, 47, 53, 0.92) 58%, #052f35 100%);"
        aria-hidden="true"
    ></div>
    <div class="absolute inset-x-0 bottom-0 -z-10 h-40 bg-gradient-to-t from-[#052f35] to-transparent" aria-hidden="true"></div>

    <header class="relative z-20 mx-auto mt-6 hidden w-[calc(100%-2rem)] max-w-7xl items-center justify-between border border-white/15 bg-[#052f35]/85 px-6 py-3 shadow-[0_24px_80px_rgba(0,0,0,0.25)] backdrop-blur lg:flex">
        <a href="{{ route('campista') }}" class="inline-flex min-w-0 items-center gap-3">
            <img
                src="{{ asset('img/logo.png') }}"
                alt="Logo do Acampamento Juvenil"
                width="56"
                height="56"
                class="juvenil-brand-mark size-12 shrink-0 object-contain"
            >
            <span class="min-w-0">
                <span class="block truncate text-sm font-black uppercase tracking-[0.22em] text-white sm:hidden">Juvenil</span>
                <span class="hidden truncate text-sm font-black uppercase tracking-[0.22em] text-white sm:block">Acampamento Juvenil</span>
                <span class="block text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-[#9ddbef] sm:text-xs sm:tracking-[0.2em]">22 a 26 de Julho</span>
            </span>
        </a>

        <nav class="hidden items-center gap-8 text-sm font-bold uppercase tracking-[0.18em] text-[#d8f2fa] lg:flex">
            <a href="#juvenil-details" class="transition-colors duration-300 hover:text-[#f46b12]" data-anchor-scroll>Detalhes</a>
            <a href="#registration" class="transition-colors duration-300 hover:text-[#f46b12]" data-anchor-scroll>Inscrição</a>
            <a href="#contact" class="transition-colors duration-300 hover:text-[#f46b12]" data-anchor-scroll>Local</a>
        </nav>

        <a
            href="#registration"
            class="hidden min-h-11 shrink-0 items-center justify-center bg-[#f46b12] px-4 text-sm font-black uppercase tracking-[0.12em] text-[#052f35] transition-colors duration-300 hover:bg-[#ff8a2a] sm:inline-flex sm:px-5"
            data-anchor-scroll
        >
            Inscrever
        </a>
    </header>

    <x-home-banner :settings="$settings ?? app(\App\Settings\GeneralSettings::class)"></x-home-banner>
</section>
