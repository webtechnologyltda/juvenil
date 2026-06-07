<section class="w-full bg-[#03181c] px-4 py-12 text-white sm:px-6 lg:px-8">
    <footer class="mx-auto flex max-w-7xl flex-col gap-8 border-t border-[#9ddbef]/20 pt-10 md:flex-row md:items-center md:justify-between" data-motion-card>
        <div class="flex items-center gap-5">
            <img
                src="{{ asset('img/logo.png') }}"
                alt="Logo do Acampamento Juvenil"
                width="72"
                height="72"
                class="juvenil-brand-mark size-16 shrink-0 object-contain"
            >
            <div>
                <p class="text-sm font-black uppercase tracking-[0.22em] text-[#f46b12]">Acampamento Juvenil</p>
                <p class="mt-2 max-w-xl text-sm leading-6 text-[#d8f2fa]">
                    De 22 a 26 de Julho. Inscrições para campistas de 29 a 59 anos.
                </p>
            </div>
        </div>

        <p class="text-sm leading-6 text-[#9ddbef] md:text-right">
            Copyright &copy; {{ now()->year }}. Desenvolvido por
            <a href="https://webtechnology.com.br" target="_blank" class="font-bold text-white transition-colors duration-300 hover:text-[#f46b12]">Web Technology</a>
        </p>
    </footer>
</section>
