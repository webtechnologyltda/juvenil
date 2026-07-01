@php
    $pixCopiaCola = $this->settings['pix_copia_cola'] ?? null;
    $pixQrCode = $this->settings['pix_qr_code'] ?? null;
    $pixQrCode = is_array($pixQrCode) ? reset($pixQrCode) : $pixQrCode;
    $pixQrCodeUrl = filled($pixQrCode) ? \Illuminate\Support\Facades\Storage::disk('public')->url($pixQrCode) : null;
    $valorAcampamento = $this->settings['valor_acampamento'] ?? null;
    $hasValorAcampamento = $valorAcampamento !== null;
    $proofAttendant = \App\Support\AtendenteWhatsapp::firstForPurpose(
        $this->settings['atendentes'] ?? [],
        \App\Support\AtendenteWhatsapp::PURPOSE_COMPROVANTE,
        $this->settings['telefone_atendente'] ?? null,
    );
    $proofAttendantWhatsappUrl = $proofAttendant['whatsapp_url'] ?? null;
    $termoResponsabilidadeUrl = \App\Support\ConfiguredStorageFile::publicUrl($this->settings['termo_responsabilidade'] ?? null);
    $registrationStatus = App\Enums\LiberacaoInscricoesStatusEnum::tryFrom($this->settings['liberacao_inscricoes_status']);
    $registrationAvailability = $this->availability;
    $registrationClosedByCapacity = $registrationStatus === App\Enums\LiberacaoInscricoesStatusEnum::LIBERADO
        && $registrationAvailability->registrationClosedByCapacity();
    $registrationStartsInFuture = $registrationStatus === App\Enums\LiberacaoInscricoesStatusEnum::LIBERADO
        && $registrationAvailability->registrationStartsInFuture();
    $registrationEndedByDate = $registrationStatus === App\Enums\LiberacaoInscricoesStatusEnum::LIBERADO
        && $registrationAvailability->registrationEnded();
    $registrationOpen = $registrationAvailability->registrationOpen();
    $hasWaitlistInvitation = $this->hasWaitlistInvitation();
    $unavailableSexMessage = $registrationAvailability->unavailableSexMessage();
    $unavailableSexes = $registrationAvailability->unavailableSexes();
    $waitlistSex = count($unavailableSexes) === 1 ? $unavailableSexes[0] : null;
@endphp

<div>
    @if($registrationStatus === App\Enums\LiberacaoInscricoesStatusEnum::LIBERADO)
        @if($this->comprado)
            <form wire:submit.prevent="compraNovaPassagem" class="p-0">
                <section class="relative flex min-h-[42rem] flex-col items-center justify-center bg-[#052f35] p-6 text-white sm:p-10">
                    <div class="mx-auto max-w-screen-md text-center lg:px-2 relative">
                        <p class="mt-8 text-2xl font-black uppercase tracking-[0.16em] text-[#f46b12]">Inscrição registrada</p>
                        <div class="flex justify-center">
                            <figure class="flex justify-center items-center mb-4 w-3/5 h-3/5">
                                <img src="{{ asset('img/campista.svg') }}" alt="" class="h-96">
                            </figure>
                        </div>
                        <p class="text-center mx-4 text-[#d8f2fa] text-sm xl:text-xl">
                            Parabéns! Você está prestes a viver o Acampamento Juvenil.
                            <br/>
                        </p>
                    </div>

                    <p class="mt-4 text-center text-2xl font-black uppercase tracking-[0.14em] text-white">Informações de pagamento</p>
                    <p class="mt-3 text-center text-sm font-bold uppercase tracking-[0.18em] text-[#9ddbef]">
                        Valor da inscrição
                        <span class="mt-2 block text-3xl font-black tracking-normal text-[#f46b12]">
                            @if($hasValorAcampamento)
                                R$ {{ number_format($valorAcampamento / 100, 2, ',', '.') }}
                            @else
                                Não configurado
                            @endif
                        </span>
                    </p>
                    @if($pixQrCodeUrl)
                        <img class="mx-auto mb-8 mt-8 bg-white p-3" src="{{ $pixQrCodeUrl }}" alt="QR Code PIX" width="150" />
                    @else
                        <div class="mx-auto mb-8 mt-8 flex min-h-[150px] w-[150px] items-center justify-center border border-[#9ddbef]/25 bg-[#052f35] p-4 text-center text-xs font-black uppercase tracking-[0.12em] text-[#9ddbef]">
                            PIX indisponível
                        </div>
                    @endif

                    <div class="grid justify-items-center mb-4">
                        @if(filled($pixCopiaCola))
                            <button
                                onclick="navigator.clipboard.writeText(@js($pixCopiaCola))"
                                type="button"
                                class="flex min-h-11 items-center justify-center bg-[#9ddbef] px-4 text-sm font-black uppercase tracking-[0.1em] text-[#052f35] transition-colors duration-300 hover:bg-white">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375 0 006.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0015 2.25h-1.5a2.251 2.251 0 00-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 00-9-9z"/>
                                </svg>
                                Copiar código PIX
                            </button>
                        @else
                            <p class="border border-[#9ddbef]/25 px-4 py-3 text-center text-sm font-bold text-[#d8f2fa]">
                                Código PIX indisponível no momento.
                            </p>
                        @endif
                    </div>

                    <div class="flex justify-evenly mx-6 mt-4 text-center">
                        <div class="justify-items-center">
                            <p class="text-center mx-4 text-[#d8f2fa] text-sm xl:text-xl">Para finalizar sua inscrição, realize o pagamento e envie o comprovante para o atendente de comprovantes.</p>
                            <div class="mt-2 grid justify-items-center">
                                @if($proofAttendantWhatsappUrl)
                                    <a target="_blank"
                                       href="{{ $proofAttendantWhatsappUrl }}"
                                       class="relative mb-8 mt-8 flex min-h-12 w-full items-center justify-center bg-[#f46b12] p-2 text-center text-sm font-black uppercase tracking-[0.12em] text-[#052f35] transition-colors duration-300 hover:bg-[#ff8a2a] lg:w-[50%]">
                                        <span class="relative text-center">Enviar comprovante</span>
                                    </a>
                                @else
                                    <p class="relative mb-8 mt-8 flex min-h-12 w-full items-center justify-center border border-[#f46b12]/35 p-2 text-center text-sm font-black uppercase tracking-[0.12em] text-[#f46b12] lg:w-[50%]">
                                        Atendente de comprovantes não configurado
                                    </p>
                                @endif
                            </div>
                            <p class="mt-6 text-[#d8f2fa]">Sua participação no Acampamento Juvenil está a um passo de ser confirmada.</p>
                        </div>
                    </div>
                    @if($termoResponsabilidadeUrl)
                        <p class="mt-4 text-center text-[#f46b12] xl:text-xl">Obrigatório levar o termo assinado no dia.</p>
                        <a href="{{ $termoResponsabilidadeUrl }}" target="_blank"
                           class="relative mb-8 mt-8 flex min-h-12 w-full items-center justify-center border border-[#f46b12]/65 p-2 text-center text-sm font-black uppercase tracking-[0.12em] text-[#f46b12] transition-colors duration-300 hover:border-white hover:text-white lg:w-[50%]">Termo de responsabilidade</a>
                    @endif
                    <button type="submit" role="button"
                            class="mb-8 mt-4 flex min-h-12 w-full items-center justify-center bg-[#9ddbef] p-2 text-sm font-black uppercase tracking-[0.12em] text-[#052f35] transition-colors duration-300 hover:bg-white lg:w-[50%]">
                        <span class="relative text-center">Nova inscrição</span>
                    </button>
                </section>
            </form>
        @elseif($registrationStartsInFuture)
            <section class="relative flex min-h-[34rem] flex-col items-center justify-center overflow-hidden bg-[#052f35] p-6 text-white sm:p-10">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_15%,rgba(157,219,239,0.16),transparent_34%),linear-gradient(180deg,rgba(5,47,53,0.82),rgba(3,27,31,0.96))]"></div>
                <div class="relative mx-auto max-w-screen-md text-center">
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-[#f46b12]">Inscrições em contagem regressiva</p>
                    <h2 class="mt-4 text-3xl font-black uppercase tracking-normal text-white sm:text-5xl">Prepare-se para se inscrever</h2>
                    <p class="mx-auto mt-4 max-w-xl text-sm font-semibold leading-7 text-[#d8f2fa] sm:text-base">
                        As inscrições começam em {{ $registrationAvailability->startsAtDisplay() }}.
                    </p>

                    <div
                        class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4"
                        data-registration-countdown
                        data-target="{{ $registrationAvailability->startsAtIso() }}"
                    >
                        <div class="border border-[#9ddbef]/25 bg-[#03272c]/80 p-4">
                            <span class="block text-4xl font-black text-[#f46b12]" data-countdown-days>--</span>
                            <span class="mt-1 block text-xs font-black uppercase tracking-[0.16em] text-[#9ddbef]">Dias</span>
                        </div>
                        <div class="border border-[#9ddbef]/25 bg-[#03272c]/80 p-4">
                            <span class="block text-4xl font-black text-[#f46b12]" data-countdown-hours>--</span>
                            <span class="mt-1 block text-xs font-black uppercase tracking-[0.16em] text-[#9ddbef]">Horas</span>
                        </div>
                        <div class="border border-[#9ddbef]/25 bg-[#03272c]/80 p-4">
                            <span class="block text-4xl font-black text-[#f46b12]" data-countdown-minutes>--</span>
                            <span class="mt-1 block text-xs font-black uppercase tracking-[0.16em] text-[#9ddbef]">Minutos</span>
                        </div>
                        <div class="border border-[#9ddbef]/25 bg-[#03272c]/80 p-4">
                            <span class="block text-4xl font-black text-[#f46b12]" data-countdown-seconds>--</span>
                            <span class="mt-1 block text-xs font-black uppercase tracking-[0.16em] text-[#9ddbef]">Segundos</span>
                        </div>
                    </div>
                </div>
            </section>
        @elseif($registrationEndedByDate)
            <section class="relative flex min-h-[34rem] flex-col items-center justify-center bg-[#052f35] p-6 text-white sm:p-10">
                <div class="mx-auto max-w-screen-md text-center lg:px-2">
                    <p class="mb-0 text-2xl font-black uppercase tracking-[0.16em] text-[#f46b12]">Inscrições encerradas</p>
                    <p class="mx-4 mt-4 text-center text-sm text-[#d8f2fa] xl:text-xl">
                        O período de inscrições encerrou em {{ $registrationAvailability->endsAtDisplay() }}.
                    </p>
                    <div class="mt-10 flex justify-center">
                        <figure class="flex items-center justify-center rounded">
                            <img src="{{ asset('img/Campfire-bro.svg') }}" alt="" class="h-80 w-full rounded-2xl animate-spin-slow">
                        </figure>
                    </div>
                </div>
            </section>
        @elseif($registrationClosedByCapacity && ! $hasWaitlistInvitation)
            <section class="relative flex min-h-[34rem] flex-col items-center justify-center bg-[#052f35] p-6 text-white sm:p-10">
                <div class="mx-auto max-w-screen-md text-center lg:px-2">
                    <p class="mb-0 text-2xl font-black uppercase tracking-[0.16em] text-[#f46b12]">Inscrições encerradas</p>
                    <h2 class="mt-4 text-3xl font-black uppercase tracking-normal text-white sm:text-5xl">Limite de inscrições atingido</h2>
                    <p class="mx-4 mt-4 text-center text-sm text-[#d8f2fa] xl:text-xl">
                        As inscrições foram encerradas pelo número de vagas preenchidas.
                    </p>
                    @livewire('campista-waitlist-form', ['sex' => $waitlistSex])
                    <div class="mt-10 flex justify-center">
                        <figure class="flex items-center justify-center rounded">
                            <img src="{{ asset('img/Campfire-bro.svg') }}" alt="" class="h-80 w-full rounded-2xl animate-spin-slow">
                        </figure>
                    </div>
                </div>
            </section>
        @elseif($registrationOpen || $hasWaitlistInvitation)
            <form
                wire:submit.prevent="submitForm"
                @class([
                    'space-y-6',
                    'light filament-registration-shell mx-auto max-w-5xl bg-white p-4 text-gray-950 shadow-[0_28px_90px_rgba(0,0,0,0.32)] ring-1 ring-[#f46b12]/35 sm:p-6 lg:p-8' => $hasWaitlistInvitation,
                ])
            >
                @unless($hasWaitlistInvitation)
                    <div class="border border-[#9ddbef]/25 bg-[#052f35] p-4 text-[#d8f2fa]" data-registration-slots>
                        <p class="text-sm font-black uppercase tracking-[0.14em] text-[#f46b12]">Inscrições abertas</p>
                        @if($registrationAvailability->endsAtDisplay())
                            <p class="mt-2 text-2xl font-black uppercase tracking-normal text-white">Falta para encerrar</p>
                            <p class="mt-1 text-sm font-semibold text-[#9ddbef]">
                                As inscrições encerram em {{ $registrationAvailability->endsAtDisplay() }}.
                            </p>
                            <div
                                class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4"
                                data-registration-countdown
                                data-target="{{ $registrationAvailability->endsAtIso() }}"
                            >
                                <div class="border border-[#9ddbef]/25 bg-[#03272c]/80 p-3 text-center">
                                    <span class="block text-3xl font-black text-[#f46b12]" data-countdown-days>--</span>
                                    <span class="mt-1 block text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#9ddbef]">Dias</span>
                                </div>
                                <div class="border border-[#9ddbef]/25 bg-[#03272c]/80 p-3 text-center">
                                    <span class="block text-3xl font-black text-[#f46b12]" data-countdown-hours>--</span>
                                    <span class="mt-1 block text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#9ddbef]">Horas</span>
                                </div>
                                <div class="border border-[#9ddbef]/25 bg-[#03272c]/80 p-3 text-center">
                                    <span class="block text-3xl font-black text-[#f46b12]" data-countdown-minutes>--</span>
                                    <span class="mt-1 block text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#9ddbef]">Minutos</span>
                                </div>
                                <div class="border border-[#9ddbef]/25 bg-[#03272c]/80 p-3 text-center">
                                    <span class="block text-3xl font-black text-[#f46b12]" data-countdown-seconds>--</span>
                                    <span class="mt-1 block text-[0.68rem] font-black uppercase tracking-[0.16em] text-[#9ddbef]">Segundos</span>
                                </div>
                            </div>
                        @else
                            <p class="mt-2 text-2xl font-black uppercase tracking-normal text-white">Inscrições abertas</p>
                        @endif
                        @if($registrationAvailability->availableSlotsMessage())
                            <p class="mt-2 text-sm font-semibold text-[#9ddbef]">
                                {{ $registrationAvailability->availableSlotsMessage() }} · {{ $registrationAvailability->activeRegistrationsMessage() }}
                            </p>
                        @endif
                    </div>
                @endunless

                @if(! $hasWaitlistInvitation && $unavailableSexMessage)
                    <div class="border border-[#f46b12]/45 bg-[#f46b12]/10 p-4 text-[#052f35]" role="alert">
                        <p class="text-sm font-black uppercase tracking-[0.14em] text-[#f46b12]">Vagas indisponíveis</p>
                        <p class="mt-2 text-sm font-semibold">{{ $unavailableSexMessage }}</p>
                        @livewire('campista-waitlist-form', ['sex' => $waitlistSex])
                    </div>
                @endif

                {{ $this->form }}
                <button type="submit" role="button"
                        class="mt-8 flex min-h-12 max-h-12 w-full items-center justify-center bg-[#f46b12] p-2 text-[18px] font-black uppercase tracking-[0.12em] text-[#052f35] transition-colors duration-300 hover:bg-[#ff8a2a] sm:max-w-full sm:p-4">
                    <i class="bi bi-cart-fill mr-2"></i>Inscrever-se
                </button>
            </form>
        @endif
    @elseif($registrationStatus === App\Enums\LiberacaoInscricoesStatusEnum::TRANCADO)
        <section class="relative flex min-h-[34rem] flex-col items-center justify-center bg-[#052f35] p-6 text-white sm:p-10">
            <div class="mx-auto max-w-screen-md text-center lg:px-2">
                <p class="mb-0 text-2xl font-black uppercase tracking-[0.16em] text-[#f46b12]">Inscrições trancadas</p>
                <p class="mx-4 mt-4 text-center text-sm text-[#d8f2fa] xl:text-xl">
                    As inscrições estão trancadas no momento.
                </p>
                @if(filled($this->settings['liberacao_inscricoes_bloco'] ?? null))
                    <div class="no-tailwind mt-6 text-[#d8f2fa]">
                        {!! $this->settings['liberacao_inscricoes_bloco'] !!}
                    </div>
                @endif
            </div>
        </section>

    @elseif($registrationStatus === App\Enums\LiberacaoInscricoesStatusEnum::ENCERRADO)
        <section class="relative flex min-h-[34rem] flex-col items-center justify-center bg-[#052f35] p-6 text-white sm:p-10">
            <div class="mx-auto max-w-screen-md text-center lg:px-2 relative">
                <div class="lg:mb-64">
                    <p class="mb-0 text-2xl font-black uppercase tracking-[0.16em] text-[#f46b12]">Inscrições encerradas</p>
                    <p class="mx-4 mt-4 text-center text-sm text-[#d8f2fa] xl:text-xl">
                        As inscrições foram encerradas manualmente pela organização.
                    </p>
                    @if(filled($this->settings['liberacao_inscricoes_bloco'] ?? null))
                        <div class="no-tailwind mt-6 text-[#d8f2fa]">
                            {!! $this->settings['liberacao_inscricoes_bloco'] !!}
                        </div>
                    @endif
                </div>

                <div class="flex justify-center">
                    <figure class="flex justify-center items-center mt-20 lg:mt-0 lg:mb-12 rounded">
                        <img src="{{ asset('img/Campfire-bro.svg') }}" alt="" class="w-full h-96 rounded-2xl animate-spin-slow">
                    </figure>
                </div>
            </div>
        </section>
    @endif

    <x-filament-actions::modals />
</div>
