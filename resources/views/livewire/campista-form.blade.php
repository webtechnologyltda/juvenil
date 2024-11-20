<div>
    @if(App\Enums\LiberacaoInscricoesStatusEnum::tryFrom($this->settings['liberacao_inscricoes_status']) == App\Enums\LiberacaoInscricoesStatusEnum::LIBERADO)
        @if($this->comprado)
            <form wire:submit.prevent="compraNovaPassagem" class="md:p-12 mx-4 p-4">
                <section class="bg-transparent text-white min-h-screen flex flex-col justify-center items-center relative">
                    <div class="mx-auto max-w-screen-md text-center lg:px-2 relative">
                        <p class="mt-8 uppercase font-bold text-2xl">Prepare-se para a Aventura!</p>
                        <div class="flex justify-center">
                            <figure class="flex justify-center items-center mb-4 w-3/5 h-3/5">
                                <img src="{{ asset('img/campista.svg') }}" alt="" class="h-96">
                            </figure>
                        </div>
                        <p class="text-center mx-4 text-yellow-500 text-sm xl:text-xl">
                            Parabéns! Você está prestes a embarcar em uma experiência inesquecível da sua vida.
                            <br/>
                        </p>
                    </div>

                    <p class="mt-4 text-white text-center text-2xl font-bangers uppercase">Informações de Pagamento</p>
                    <img class="mt-8 mb-8 mx-auto" src="{{ asset('img/qr_code_pix.png') }}?20231021" alt="qrcode pix" width="150" />

                    <div class="grid justify-items-center mb-4">
                        <button
                            onclick="navigator.clipboard.writeText('00020126910014br.gov.bcb.pix01368c973c48-8687-4977-b585-cb4cfeb9d7a30229ACAMPAMENTO TK  NAVEGANTES SC5204000053039865406250.005802BR5919DIOCESE DE BLUMENAU6010NAVEGANTES62290525VE7Y10G0K87QFK28YPAD0HGV463047FBD')"
                            type="button"
                            class="text-gray-950 text-md bg-gray-200 rounded-sm p-2 flex justify-between">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375 0 006.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0015 2.25h-1.5a2.251 2.251 0 00-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 00-9-9z"/>
                            </svg>
                            Copiar código PIX
                        </button>
                    </div>

                    <div class="flex justify-evenly mx-6 mt-4 text-center">
                        <div class="justify-items-center">
                            <p class="text-center mx-4 text-yellow-500 text-sm xl:text-xl">Para finalizar sua incrição realize o pagamente e envie o comprovante para nosso atendente </p>
                            <div class="mt-2 grid justify-items-center">
                                <a target="_blank"
                                   href="https://wa.me/55{{str_replace(['(', ')', '-', ' '], '', $this->settings['telefone_atendente'])}}?text=Ol%C3%A1+tenho+uma+d%C3%BAvida+sobre+o+Trekking%2C+consegue+me+ajudar+%3F"
                                   class=" bg-color1 rounded mt-8 mb-8 p-2 w-full lg:w-[50%]  sm:p-4sm:max-w-full
                                   flex items-center justify-center text-[12px] hover:bg-amber-600 hover:font-bold
                                   transition-all duration-500 relative text-center text-md text-black font-bold ">
                                    <span class="relative text-center text-lg text-gray-950 font-bold">Falar com atendente</span>
                                </a>
                            </div>
                            <p class="mt-6">Sua aventura está a apenas um passo de começar! 🌄👣</p>
                        </div>
                    </div>
                    <p class="text-center  mt-4 text-red-600 xl:text-xl">Obrigatório <br>levar termo assinado no dia.  </p>
                    <a href="{{ route('pdf.show', ['filename' =>'termo.pdf'  ]) }}"
                       class="bg-red-600 rounded mt-8 mb-8 p-2 w-full lg:w-[50%]  sm:p-4sm:max-w-full
                                   flex items-center justify-center text-[12px] hover:bg-amber-600 hover:font-bold
                                   transition-all duration-500 relative text-center text-md text-black font-bold">TERMO RESPONDABILIDADE</a>
                    <button type="submit" role="button"
                            class="bg-color3 rounded mt-8 mb-8 p-2 w-full lg:w-[50%]
                                   flex items-center justify-center text-[12px] hover:bg-amber-600 hover:font-bold
                                   transition-all duration-500">
                        <span class="relative text-center text-lg text-gray-950 font-bold">Nova Inscrição</span>
                    </button>
                </section>
            </form>
        @else
            <form wire:submit.prevent="submitForm" class="md:p-12 mx-4 p-4">
                {{ $this->form }}
                <button type="submit" role="button"
                        class="bg-color1 rounded mt-8 p-2 w-full text-gray-800 sm:p-4 sm:max-w-full flex items-center justify-center hover:text-[20px] min-h-12 max-h-12 transition-all duration-500 text-[18px] hover:bg-[#f6b53c]">
                    <i class="bi bi-cart-fill mr-2"></i>Comprar
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
        <section class="text-white min-h-screen flex flex-col justify-center items-center relative">
            <div id="particles-js2" class="min-h-screen"></div>
            <div class="mx-auto max-w-screen-md text-center lg:px-2 relative">
                <div class="lg:mb-64">
                    <p class="uppercase font-bold text-2xl text-amber-500 mb-0">Inscrições Encerradas</p>
                    <p class="text-center mx-4 text-white text-sm xl:text-xl">
                        @if(\Carbon\Carbon::now() < Carbon\Carbon::create(2024,11,01,19,00,00))
                            Pronto para algo novo? A aventura está prestes a começar! 🏞️🌲
                        @else
                            Prontos, aventureiros? 🌲
                        @endif
                    </p>
                </div>

                <div class="flex justify-center">
                    <figure class="flex justify-center items-center mt-20 lg:mt-0 lg:mb-12 rounded">
                        <img src="{{ asset('img/Campfire-bro.svg') }}" alt="" class="w-full h-96 rounded-2xl animate-spin-slow">
                    </figure>
                </div>

                @if(\Carbon\Carbon::now() < Carbon\Carbon::create(2024,8,23,19,00,00))
                    <div class="row time-countdown justify-content-center p-0 mb-4 mt-0 pt-0 z-10">
                        <div id="clockForm" class="time-count"></div>
                    </div>
                @endif
            </div>
        </section>
    @endif
</div>
