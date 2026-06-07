<section id="juvenil-details" class="juvenil-experience-section relative isolate overflow-hidden px-4 py-14 text-white sm:px-6 sm:py-20 lg:px-8 lg:py-28">
    <div class="absolute inset-x-0 top-0 h-px bg-[#9ddbef]/25" aria-hidden="true"></div>
    <div class="absolute inset-x-0 bottom-0 h-px bg-[#9ddbef]/20" aria-hidden="true"></div>

    <div class="juvenil-experience-shell relative mx-auto grid max-w-7xl gap-8 lg:grid-cols-[minmax(0,1.16fr)_minmax(21rem,0.84fr)] lg:items-stretch">
        <figure class="juvenil-experience-video relative min-h-[31rem] overflow-hidden border border-[#9ddbef]/25 bg-[#03181c] sm:min-h-[36rem] lg:min-h-[38rem]" data-gsap-image data-motion-card>
            <video
                class="juvenil-site-video"
                autoplay
                muted
                loop
                playsinline
                preload="auto"
                disablepictureinpicture
                aria-label="Vídeo ambiente do Acampamento Juvenil"
            >
                <source src="{{ asset('img/barraca.mp4') }}" type="video/mp4">
                Seu navegador não suporta reprodução de vídeos.
            </video>

            <div class="juvenil-experience-video__shade" aria-hidden="true"></div>
            <div class="juvenil-experience-video__rail" aria-hidden="true">
                <span>Juvenil</span>
                <span>22-26 Julho</span>
                <span>Campistas</span>
            </div>

            <figcaption class="absolute inset-x-0 bottom-0 z-10 p-5 sm:p-7 lg:p-8 lg:pr-20">
                <div class="grid gap-4 border-t border-[#9ddbef]/28 pt-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                    <div>
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#9ddbef]">Vivência do acampamento</p>
                        <p class="mt-2 max-w-md text-xl font-black uppercase leading-[0.98] text-white sm:text-2xl">
                            Barraca, fogo e convivência.
                        </p>
                    </div>
                    <a href="#registration" class="inline-flex w-fit items-center gap-2 bg-[#f46b12] px-4 py-3 text-xs font-black uppercase tracking-[0.16em] text-[#052f35] transition hover:bg-[#ff7a19]" data-anchor-scroll>
                        Inscrever
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </figcaption>
        </figure>

        <div class="juvenil-experience-copy grid content-between gap-8 border border-[#9ddbef]/25 bg-[#073d45]/72 p-5 backdrop-blur sm:p-7 lg:p-8" data-motion-card>
            <div>
                <p class="text-[0.68rem] font-black uppercase tracking-[0.28em] text-[#f46b12]" data-motion-heading>Experiência</p>
                <h2 class="mt-4 max-w-sm text-3xl font-black uppercase leading-[0.98] tracking-normal text-white sm:text-4xl lg:text-4xl" data-motion-heading>
                    Acampamento para viver de perto
                </h2>
                <p class="mt-5 max-w-md text-base leading-7 text-[#d8f2fa]" data-scrub-reveal>
                    Dias de fé, amizade e convivência em uma programação feita para campistas.
                </p>
            </div>

            <div class="juvenil-experience-facts divide-y divide-[#9ddbef]/18 border-y border-[#9ddbef]/18">
                <div class="grid grid-cols-[5.5rem_minmax(0,1fr)] gap-4 py-5">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#9ddbef]">Data</p>
                    <p class="text-2xl font-black uppercase leading-none text-[#f46b12]">22 a 26 de Julho</p>
                </div>
                <div class="grid grid-cols-[5.5rem_minmax(0,1fr)] gap-4 py-5">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#9ddbef]">Idade</p>
                    <p class="text-lg font-black uppercase leading-tight text-white">29 a 59 anos</p>
                </div>
                <div class="grid grid-cols-[5.5rem_minmax(0,1fr)] gap-4 py-5">
                    <p class="text-[0.68rem] font-black uppercase tracking-[0.22em] text-[#9ddbef]">Vagas</p>
                    <p class="text-lg font-black uppercase leading-tight text-white">Limitadas</p>
                </div>
            </div>

            <div class="juvenil-experience-note flex items-start gap-4 border border-[#f46b12]/42 bg-[#f46b12]/10 p-4">
                <i class="bi bi-calendar2-check mt-1 text-lg text-[#f46b12]" aria-hidden="true"></i>
                <p class="text-sm leading-6 text-[#d8f2fa]">
                    Inscrições a partir de 07 de Junho, após a Santa Missa das 19h30.
                </p>
            </div>
        </div>
    </div>
</section>
