<div class="juvenil-admin-login relative isolate min-h-[100dvh] overflow-hidden bg-[#03181c] px-4 py-8 text-white sm:px-6 lg:px-8">
    <div class="absolute inset-0 -z-30 bg-[url('/img/hero-mobile.webp')] bg-cover bg-center opacity-42 lg:bg-[url('/img/hero-desktop.webp')]" aria-hidden="true"></div>
    <div class="absolute inset-0 -z-20 bg-[radial-gradient(circle_at_22%_16%,rgba(157,219,239,0.24),transparent_32rem),linear-gradient(90deg,rgba(3,24,28,0.94)_0%,rgba(5,47,53,0.88)_48%,rgba(3,24,28,0.98)_100%)]" aria-hidden="true"></div>
    <div class="absolute inset-x-0 bottom-0 -z-10 h-48 bg-gradient-to-t from-[#03181c] to-transparent" aria-hidden="true"></div>

    <main class="mx-auto grid min-h-[calc(100dvh-4rem)] w-full max-w-7xl items-center gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(24rem,0.72fr)]">
        <section class="hidden lg:block">
            <a href="{{ route('campista') }}" class="inline-flex items-center gap-4 border border-[#9ddbef]/20 bg-[#052f35]/78 px-5 py-4 shadow-[0_24px_80px_rgba(0,0,0,0.32)] backdrop-blur-xl">
                <img
                    src="{{ asset('img/logo.webp') }}"
                    alt="Logo do Acampamento Juvenil"
                    width="72"
                    height="72"
                    class="size-16 shrink-0 object-contain drop-shadow-[0_12px_24px_rgba(0,0,0,0.35)]"
                >
                <span>
                    <span class="block text-sm font-black uppercase tracking-[0.24em] text-white">Acampamento Juvenil</span>
                    <span class="mt-1 block text-xs font-black uppercase tracking-[0.2em] text-[#9ddbef]">22 a 26 de Julho</span>
                </span>
            </a>

            <div class="mt-16 max-w-3xl">
                <p class="text-xs font-black uppercase tracking-[0.32em] text-[#f46b12]">Painel do sistema</p>
                <h1 class="mt-5 max-w-3xl text-6xl font-black uppercase leading-[0.9] tracking-normal text-white">
                    Organização do <span class="juvenil-poster-title block text-[#f46b12]">Juvenil</span>
                </h1>
                <p class="mt-6 max-w-xl text-base leading-8 text-[#d8f2fa]">
                    Centralize a operação do Acampamento Juvenil em um painel feito para acompanhar inscrições, pagamentos e campistas.
                </p>
            </div>

            <div class="juvenil-admin-ops-panel mt-10 max-w-3xl border border-[#9ddbef]/20 bg-[#052f35]/74 p-5 shadow-[0_28px_90px_rgba(0,0,0,0.28)] backdrop-blur-xl">
                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_11rem] lg:items-stretch">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="inline-flex min-h-8 items-center border border-[#f46b12]/55 bg-[#f46b12]/12 px-3 text-xs font-black uppercase tracking-[0.22em] text-[#f46b12]">
                                Operação do evento
                            </span>
                            <span class="text-xs font-black uppercase tracking-[0.2em] text-[#9ddbef]">painel administrativo</span>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <div class="grid gap-3 border-t border-[#9ddbef]/16 pt-4">
                                <span class="size-3 bg-[#f46b12]" aria-hidden="true"></span>
                                <div>
                                    <p class="text-base font-black text-white">Inscrições organizadas</p>
                                    <p class="mt-1 text-xs font-bold uppercase leading-5 tracking-[0.12em] text-[#9ddbef]">cadastro e documentos</p>
                                </div>
                            </div>
                            <div class="grid gap-3 border-t border-[#9ddbef]/16 pt-4">
                                <span class="size-3 bg-[#9ddbef]" aria-hidden="true"></span>
                                <div>
                                    <p class="text-base font-black text-white">Pagamentos acompanhados</p>
                                    <p class="mt-1 text-xs font-bold uppercase leading-5 tracking-[0.12em] text-[#9ddbef]">conferência financeira</p>
                                </div>
                            </div>
                            <div class="grid gap-3 border-t border-[#9ddbef]/16 pt-4">
                                <span class="size-3 bg-white" aria-hidden="true"></span>
                                <div>
                                    <p class="text-base font-black text-white">Dados para a equipe</p>
                                    <p class="mt-1 text-xs font-bold uppercase leading-5 tracking-[0.12em] text-[#9ddbef]">acolhida e organização</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="relative grid content-between overflow-hidden border border-[#f46b12]/45 bg-[#f46b12] p-4 text-[#052f35]">
                        <div class="absolute -right-8 -top-8 size-24 rounded-full bg-white/18" aria-hidden="true"></div>
                        <div class="relative">
                            <p class="text-xs font-black uppercase tracking-[0.22em]">Julho</p>
                            <p class="mt-3 whitespace-nowrap text-5xl font-black leading-none tracking-normal">22-26</p>
                        </div>
                        <p class="relative mt-6 text-xs font-black uppercase leading-5 tracking-[0.16em]">acesso restrito</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="juvenil-admin-login-card mx-auto w-full max-w-md border border-[#9ddbef]/22 bg-[#071d25]/90 p-5 shadow-[0_34px_110px_rgba(0,0,0,0.45)] backdrop-blur-2xl sm:p-8">
            <div class="mb-8 text-center">
                <a href="{{ route('campista') }}" class="mx-auto inline-flex items-center justify-center">
                    <img
                        src="{{ asset('img/logo.webp') }}"
                        alt="Logo do Acampamento Juvenil"
                        width="112"
                        height="112"
                        class="size-24 object-contain drop-shadow-[0_16px_32px_rgba(0,0,0,0.36)]"
                    >
                </a>
                <p class="mt-5 text-xs font-black uppercase tracking-[0.28em] text-[#f46b12]">Acesso restrito</p>
                <h2 class="mt-3 text-3xl font-black uppercase leading-tight text-white">Entrar no painel</h2>
                <p class="mt-3 text-sm leading-6 text-[#d8f2fa]">Use suas credenciais para gerenciar o Acampamento Juvenil.</p>
            </div>

            {{ $this->content }}

            <div class="mt-7 border-t border-[#9ddbef]/16 pt-5 text-center">
                <a href="{{ route('campista') }}" class="text-xs font-black uppercase tracking-[0.18em] text-[#9ddbef] transition-colors hover:text-[#f46b12]">
                    Voltar para o site
                </a>
            </div>
        </section>
    </main>

    <x-filament-actions::modals />
</div>
