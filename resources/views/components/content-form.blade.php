@php
    $pixCopiaCola = $settings->pix_copia_cola;
    $pixQrCode = is_array($settings->pix_qr_code) ? reset($settings->pix_qr_code) : $settings->pix_qr_code;
    $pixQrCodeUrl = filled($pixQrCode) ? \Illuminate\Support\Facades\Storage::disk('public')->url($pixQrCode) : null;
    $valorAcampamento = $settings->valor_acampamento;
    $hasValorAcampamento = $valorAcampamento !== null;
    $attendanceContacts = is_array($settings->atendentes ?? null) ? $settings->atendentes : [];
    $questionAttendants = \App\Support\AtendenteWhatsapp::forPurpose(
        $attendanceContacts,
        \App\Support\AtendenteWhatsapp::PURPOSE_DUVIDAS,
        $settings->telefone_atendente,
        2,
    );
    $specificAttendants = \App\Support\AtendenteWhatsapp::forPurpose(
        $attendanceContacts,
        \App\Support\AtendenteWhatsapp::PURPOSE_NECESSIDADE_ESPECIFICA,
    );
    $termoResponsabilidadeUrl = \App\Support\ConfiguredStorageFile::publicUrl($settings->termo_responsabilidade);
@endphp

<section id="registration" class="juvenil-form-shell relative z-10 bg-[#06343b] px-4 py-20 text-white sm:px-6 sm:py-24 lg:px-8 lg:py-28">
    <div
        class="absolute inset-0 opacity-70"
        style="background: linear-gradient(180deg, #052f35 0%, #06343b 34%, #041f24 100%);"
        aria-hidden="true"
    ></div>

    <div class="relative mx-auto w-full max-w-7xl">
        <div class="max-w-3xl">
            <p class="text-xs font-black uppercase tracking-[0.28em] text-[#9ddbef]" data-motion-heading>Faça sua inscrição</p>
            <h2 class="mt-4 text-4xl font-black uppercase leading-[0.95] text-white sm:text-5xl lg:text-6xl" data-motion-heading>
                Campistas do Acampamento <span class="text-[#f46b12]">Juvenil</span>
            </h2>
            <p class="mt-6 max-w-2xl text-base leading-7 text-[#d8f2fa]">
                Preencha seus dados com atenção. Depois, conclua o pagamento e fale com o atendente se precisar de ajuda.
            </p>
        </div>

        <div class="mt-8 grid w-full items-start gap-6 sm:mt-10 lg:grid-cols-[minmax(0,24rem)_minmax(0,1fr)] lg:gap-10">
            <aside class="order-2 border border-[#9ddbef]/25 bg-[#073d45] p-5 shadow-[0_22px_70px_rgba(0,0,0,0.22)] sm:p-8 lg:order-1" data-motion-card>
                <p class="text-center text-sm font-black uppercase tracking-[0.24em] text-[#f46b12]">Instruções</p>
                <ol class="mt-7 space-y-4 text-sm leading-6 text-[#d8f2fa]">
                    <li class="grid grid-cols-[2rem_1fr] gap-3">
                        <span class="flex size-8 items-center justify-center bg-[#f46b12] text-sm font-black text-[#052f35]">1</span>
                        <span>Preencha o formulário com seus dados corretamente.</span>
                    </li>
                    <li class="grid grid-cols-[2rem_1fr] gap-3">
                        <span class="flex size-8 items-center justify-center bg-[#f46b12] text-sm font-black text-[#052f35]">2</span>
                        <span>Se o pagamento for PIX, escaneie o QR Code ou copie o código no app do banco.</span>
                    </li>
                    <li class="grid grid-cols-[2rem_1fr] gap-3">
                        <span class="flex size-8 items-center justify-center bg-[#f46b12] text-sm font-black text-[#052f35]">3</span>
                        <span>Em caso de dúvida ou outra forma de pagamento, fale com um atendente.</span>
                    </li>
                </ol>

                <div class="mt-10 border-t border-[#9ddbef]/20 pt-8">
                    <p class="text-center text-2xl font-black uppercase leading-tight text-white">Informações de pagamento</p>
                    @if($pixQrCodeUrl)
                        <img
                            class="mx-auto mb-7 mt-7 bg-white p-3"
                            src="{{ $pixQrCodeUrl }}"
                            alt="QR Code PIX para pagamento da inscrição"
                            width="170"
                        >
                    @else
                        <div class="mx-auto mb-7 mt-7 flex min-h-[170px] w-[170px] items-center justify-center border border-[#9ddbef]/25 bg-[#052f35] p-4 text-center text-xs font-black uppercase tracking-[0.12em] text-[#9ddbef]">
                            PIX indisponível
                        </div>
                    @endif

                    @if(filled($pixCopiaCola))
                        <button
                            onclick="navigator.clipboard.writeText(@js($pixCopiaCola))"
                            type="button"
                            class="inline-flex min-h-11 w-full items-center justify-center bg-[#9ddbef] px-4 text-sm font-black uppercase tracking-[0.1em] text-[#052f35] transition-colors duration-300 hover:bg-white"
                        >
                            Copiar código PIX
                        </button>
                    @else
                        <p class="border border-[#9ddbef]/25 px-4 py-3 text-center text-sm font-bold text-[#d8f2fa]">
                            Código PIX indisponível no momento.
                        </p>
                    @endif

                    <p class="mt-7 text-center text-sm font-bold uppercase tracking-[0.18em] text-[#9ddbef]">
                        Valor da inscrição
                        <span class="mt-2 block text-3xl font-black tracking-normal text-[#f46b12]">
                            @if($hasValorAcampamento)
                                R$ {{ number_format($valorAcampamento / 100, 2, ',', '.') }}
                            @else
                                Não configurado
                            @endif
                        </span>
                    </p>
                </div>

                <div class="mt-8 grid gap-3">
                    @if($questionAttendants !== [])
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-[#9ddbef]">Atendimento para dúvidas</p>
                        @foreach($questionAttendants as $attendant)
                            <a
                                target="_blank"
                                href="{{ $attendant['whatsapp_url'] }}"
                                class="inline-flex min-h-12 w-full items-center justify-center bg-[#f46b12] px-4 text-center text-sm font-black uppercase tracking-[0.12em] text-[#052f35] transition-colors duration-300 hover:bg-[#ff8a2a]"
                            >
                                Dúvidas - {{ $attendant['nome'] }}
                            </a>
                        @endforeach
                    @else
                        <p class="inline-flex min-h-12 w-full items-center justify-center border border-[#f46b12]/35 px-4 text-center text-sm font-black uppercase tracking-[0.12em] text-[#f46b12]">
                            Atendente não configurado
                        </p>
                    @endif

                    @if($specificAttendants !== [])
                        <div class="mt-3 border-t border-[#9ddbef]/20 pt-4">
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-[#9ddbef]">Necessidades específicas</p>
                            <div class="mt-3 grid gap-3">
                                @foreach($specificAttendants as $attendant)
                                    <a
                                        target="_blank"
                                        href="{{ $attendant['whatsapp_url'] }}"
                                        class="inline-flex min-h-12 w-full items-center justify-center border border-[#f46b12]/65 px-4 text-center text-sm font-black uppercase tracking-[0.12em] text-[#f46b12] transition-colors duration-300 hover:border-white hover:text-white"
                                    >
                                        {{ $attendant['nome'] }}
                                    </a>
                                    @if(filled($attendant['observacao']))
                                        <p class="-mt-1 text-sm leading-6 text-[#d8f2fa]">{{ $attendant['observacao'] }}</p>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($termoResponsabilidadeUrl)
                        <a
                            href="{{ $termoResponsabilidadeUrl }}"
                            target="_blank"
                            class="inline-flex min-h-12 w-full items-center justify-center border border-[#f46b12]/65 px-4 text-center text-sm font-black uppercase tracking-[0.12em] text-[#f46b12] transition-colors duration-300 hover:border-white hover:text-white"
                        >
                            Termo de responsabilidade
                        </a>
                    @endif
                </div>
            </aside>

            <div class="light filament-registration-shell order-1 min-w-0 overflow-visible bg-white p-3 text-gray-950 shadow-[0_28px_90px_rgba(0,0,0,0.32)] ring-1 ring-[#f46b12]/35 sm:p-6 lg:order-2 lg:p-8" data-motion-card>
                @livewire('campista-form')
            </div>
        </div>
    </div>
</section>
