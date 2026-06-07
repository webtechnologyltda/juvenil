@php
    $pixCopiaCola = $this->settings['pix_copia_cola'] ?? null;
    $pixQrCode = $this->settings['pix_qr_code'] ?? null;
    $pixQrCode = is_array($pixQrCode) ? reset($pixQrCode) : $pixQrCode;
    $pixQrCodeUrl = filled($pixQrCode) ? \Illuminate\Support\Facades\Storage::disk('public')->url($pixQrCode) : null;
    $valorAcampamento = $this->settings['valor_acampamento'] ?? null;
    $hasValorAcampamento = $valorAcampamento !== null;
    $attendantWhatsappUrl = \App\Support\AtendenteWhatsapp::url($this->settings['telefone_atendente'] ?? null);
    $termoResponsabilidadeUrl = \App\Support\ConfiguredStorageFile::publicUrl($this->settings['termo_responsabilidade'] ?? null);
@endphp

<div>
    @if(App\Enums\LiberacaoInscricoesStatusEnum::tryFrom($this->settings['liberacao_inscricoes_status']) == App\Enums\LiberacaoInscricoesStatusEnum::LIBERADO)
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
                            <p class="text-center mx-4 text-[#d8f2fa] text-sm xl:text-xl">Para finalizar sua inscrição, realize o pagamento e envie o comprovante para nosso atendente.</p>
                            <div class="mt-2 grid justify-items-center">
                                @if($attendantWhatsappUrl)
                                    <a target="_blank"
                                       href="{{ $attendantWhatsappUrl }}"
                                       class="relative mb-8 mt-8 flex min-h-12 w-full items-center justify-center bg-[#f46b12] p-2 text-center text-sm font-black uppercase tracking-[0.12em] text-[#052f35] transition-colors duration-300 hover:bg-[#ff8a2a] lg:w-[50%]">
                                        <span class="relative text-center">Falar com atendente</span>
                                    </a>
                                @else
                                    <p class="relative mb-8 mt-8 flex min-h-12 w-full items-center justify-center border border-[#f46b12]/35 p-2 text-center text-sm font-black uppercase tracking-[0.12em] text-[#f46b12] lg:w-[50%]">
                                        Atendente não configurado
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
        @else
            <form wire:submit.prevent="submitForm" class="space-y-6">
                {{ $this->form }}
                <button type="submit" role="button"
                        class="mt-8 flex min-h-12 max-h-12 w-full items-center justify-center bg-[#f46b12] p-2 text-[18px] font-black uppercase tracking-[0.12em] text-[#052f35] transition-colors duration-300 hover:bg-[#ff8a2a] sm:max-w-full sm:p-4">
                    <i class="bi bi-cart-fill mr-2"></i>Inscrever-se
                </button>
            </form>
        @endif
    @elseif(App\Enums\LiberacaoInscricoesStatusEnum::tryFrom($this->settings['liberacao_inscricoes_status']) == App\Enums\LiberacaoInscricoesStatusEnum::TRANCADO)
        <section class="bg-transparent text-white min-h-screen flex flex-col justify-center items-center relative">
            <div class="no-tailwind">
                {!! $this->settings['liberacao_inscricoes_bloco'] !!}
            </div>
        </section>

    @elseif(App\Enums\LiberacaoInscricoesStatusEnum::tryFrom($this->settings['liberacao_inscricoes_status']) == App\Enums\LiberacaoInscricoesStatusEnum::ENCERRADO)
        <section class="relative flex min-h-[34rem] flex-col items-center justify-center bg-[#052f35] p-6 text-white sm:p-10">
            <div class="mx-auto max-w-screen-md text-center lg:px-2 relative">
                <div class="lg:mb-64">
                    <p class="mb-0 text-2xl font-black uppercase tracking-[0.16em] text-[#f46b12]">Inscrições encerradas</p>
                    <p class="mx-4 mt-4 text-center text-sm text-[#d8f2fa] xl:text-xl">
                        As inscrições do Acampamento Juvenil não estão disponíveis no momento.
                    </p>
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
