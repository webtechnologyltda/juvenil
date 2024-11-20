<section id="registration" class="relative z-10 pt-32 backdrop-blur-3xl lg:pb-32 lg:pt-0">
    <div class="mx-auto max-w-7xl  lg:px-12 xl:px-6 2xl:px-0">
        <div class="flex flex-wrap items-center gap-6">
            <p class="text-color3 text-5xl font-bold uppercase py-12 font-secondary my-4 ml-4">Inscrição  </p>
        </div>
        <div class="flex flex-wrap items-center gap-6 ">
            <div class="grid gap-6 border-t border-white/30  mb-8 lg:grid-cols-3 lg:gap-20 ">
                <div class="mt-8 border border-gray-400 bg-gray-400/5 p-8 sm:p-12 mx-4">
                    <p class="text-amber-500 text-center text-xl uppercase font-mono">Instruções</p>
                    <ol class="text-gray-400 text-center text-md" type="1">
                        <li class="list-decimal mt-2">Preencha o Formulário com seus dados corretamente.
                        </li>
                        <li class="list-decimal mt-2">Após o preenchimento <b>caso o pagamento desejado
                                seja
                                PIX</b>, direcione a camera do celular ou copie o código PIX e cole no app
                            do  seu banco, e realize o pagamento.
                            <br/> Caso seu pagamento não seja PIX pule para o item 3.
                        </li>
                        <li class="list-decimal mt-2">Clique em "Falar com um atendente" caso tenha alguma dúvida</li>
                    </ol>
                    <p class="mt-20 text-white text-center text-2xl font-bangers uppercase">Informações de
                        Pagamento</p>
                    <img class="mt-8 mb-8 mx-auto"
                         src="{{ asset('img/qr_code_pix.png') }}?20231021" alt="qrcode pix"
                         width="150"/>
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
                    <p class="text-white text-center text-sm">Favorecido<br> Diocese de Blumenau<br>
                        03.925.280/0035-86 <br>Instituição: CC da Foz do Rio Itajaí Açu</p>
                    <p class="text-white text-center text-2xl mt-8 font-bangers">Valor da Inscrição<br></br>
                        <span class="text-yellow-500 font-bold text-2xl text-center text-md mt-0">R$ 250,00</span>
                    </p>

                    <p class="text-white text-center text-sm mt-8">
                        Outras formas de pagamento, falar com atendente
                    </p>
                    <div class="mt-2 grid justify-items-center">
                        <a target="_blank"
                           href="https://wa.me/55{{str_replace(['(', ')', '-', ' '], '', $settings->telefone_atendente)}}?text=Ol%C3%A1+tenho+uma+d%C3%BAvida+sobre+o+Trekking%2C+consegue+me+ajudar+%3F"
                           class=" bg-color3 rounded mt-8 mb-8 p-2 w-full  text-white sm:p-4sm:max-w-full
                                   flex items-center justify-center text-[12px] hover:bg-amber-600 hover:font-bold
                                   transition-all duration-500">
                            <span class="relative text-center text-lg text-gray-950">Falar com atendente</span>
                        </a>
                    </div>
                    <p class="text-center  text-red-600 xl:text-md ">Obrigatório <br>levar termo assinado no dia.  </p>
                    <a href="{{ route('pdf.show', ['filename' =>'termo.pdf'  ]) }}"
                       class=" bg-red-600 rounded mt-8 mb-8 p-2 w-full   sm:p-4sm:max-w-full
                                   flex items-center justify-center text-[12px] hover:bg-amber-600 hover:font-bold
                                   transition-all duration-500 relative text-center text-md text-black font-bold">TERMO RESPONDABILIDADE</a>
                </div>
                <div class="mt-8 lg:col-span-2  border border-gray-400 bg-gray-400/5 mx-6">
                    @livewire('campista-form')
                </div>

            </div>
        </div>
    </div>
</section>
