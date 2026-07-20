<section class="juvenil-footer w-full overflow-hidden bg-[#03181c] px-4 pb-24 pt-12 text-white sm:px-6 lg:px-8 lg:pb-14">
    <footer class="mx-auto max-w-7xl border-t border-[#9ddbef]/20 pt-10" data-motion-card>
        <div class="grid gap-6 text-center sm:grid-cols-[auto_minmax(0,1fr)] sm:items-center sm:text-left">
            <span class="mx-auto flex size-36 shrink-0 items-center justify-center rounded-full bg-[#d8f2fa] p-3 shadow-[0_24px_70px_rgba(157,219,239,0.14)] ring-1 ring-white/40 sm:mx-0 lg:size-40">
                <img
                    src="{{ asset('img/logo.webp') }}"
                    alt="Logo do Acampamento Juvenil"
                    width="160"
                    height="160"
                    class="juvenil-brand-mark size-full object-contain"
                >
            </span>

            <div>
                <p class="juvenil-poster-title text-4xl uppercase leading-none text-white sm:text-5xl">Juvenil</p>
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-6 text-[#d8f2fa] sm:mx-0">
                    De 22 a 26 de Julho. Inscrições para campistas de 29 a 59 anos.
                </p>

                <div class="mt-7 grid gap-3 sm:max-w-2xl sm:grid-cols-3">
                    <a
                        href="#registration"
                        class="inline-flex min-h-12 items-center justify-center bg-[#f46b12] px-5 text-sm font-black uppercase tracking-[0.12em] text-[#052f35] transition-colors duration-300 hover:bg-[#ff8a2a]"
                        data-anchor-scroll
                    >
                        Inscrever
                    </a>
                    <a
                        href="{{ route('termos-inscricao') }}"
                        class="inline-flex min-h-12 items-center justify-center border border-[#9ddbef]/30 px-5 text-sm font-black uppercase tracking-[0.12em] text-[#d8f2fa] transition-colors duration-300 hover:border-[#f46b12] hover:text-[#f46b12]"
                    >
                        Termos
                    </a>
                    <a
                        href="{{ route('politica-privacidade') }}"
                        class="inline-flex min-h-12 items-center justify-center border border-[#9ddbef]/30 px-5 text-sm font-black uppercase tracking-[0.12em] text-[#d8f2fa] transition-colors duration-300 hover:border-[#f46b12] hover:text-[#f46b12]"
                    >
                        Privacidade
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-5 border-t border-[#9ddbef]/15 pt-6 text-center text-sm leading-6 text-[#9ddbef] md:text-left">
            <nav class="flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-xs font-black uppercase tracking-[0.18em] md:justify-start" aria-label="Links do rodapé">
                <a href="#top" class="transition-colors duration-300 hover:text-[#f46b12]" data-anchor-scroll>Início</a>
                <a href="#juvenil-details" class="transition-colors duration-300 hover:text-[#f46b12]" data-anchor-scroll>Detalhes</a>
                <a href="#contact" class="transition-colors duration-300 hover:text-[#f46b12]" data-anchor-scroll>Local</a>
            </nav>

            <p>
                Copyright &copy; {{ now()->year }}. Desenvolvido por
                <a href="https://webtechnology.com.br" target="_blank" class="font-bold text-white transition-colors duration-300 hover:text-[#f46b12]">Web Technology</a>
            </p>
        </div>
    </footer>
</section>
