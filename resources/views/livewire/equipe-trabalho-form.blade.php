<div>
    @if(App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum::tryFrom($this->settings['liberacao_inscricoes_equipe_trabalho_status']) == App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum::LIBERADO)
        @if($this->inscrito)
            <form wire:submit.prevent="realizarNovaInscricao" class="md:p-12 mx-4 p-4">
                <section
                    class="bg-transparent text-white min-h-screen flex flex-col justify-center items-center relative">
                    <div class="mx-auto max-w-screen-md text-center lg:px-2 relative">
                        <p class="mt-8 uppercase font-bold text-2xl">Agradecemos a sua inscrição!</p>
                        <div class="flex justify-center">
                            <figure class="flex justify-center items-center mb-4 w-3/5 h-3/5">
                                <img src="{{ asset('img/equipe.svg') }}" alt="" class="h-96">
                            </figure>
                        </div>
                        <p class="text-center mx-4 text-yellow-500 text-sm xl:text-xl">
                            <span>Muito obrigado, sua participação não somente ajudará o acampamento Trekking, como marcará na vida dos nossos campistas!</span>
                            <br/>
                            <span class="text-white mt-4">Deus abençoe pelo seu sim !</span>
                        </p>
                    </div>
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
                    <i class="fluentui-document-checkmark-20 text-black mr-2"></i>Inscrever-se
                </button>
            </form>
        @endif
    @elseif(App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum::tryFrom($this->settings['liberacao_inscricoes_equipe_trabalho_status']) == App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum::TRANCADO)
        <section class="bg-transparent text-white min-h-screen flex flex-col justify-center items-center relative">
            <div class="flex justify-center">
                <img src="{{ asset('img/equipe.svg') }}" class="w-full"/>
            </div>
            <div class="text-5xl font-bold text-white uppercase text-center pt-8 pb-8">
                Computando inscrições...
            </div>
        </section>

    @elseif(App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum::tryFrom($this->settings['liberacao_inscricoes_equipe_trabalho_status']) == App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum::ENCERRADO)
        <section class="text-white min-h-screen flex flex-col justify-center items-center relative">
            <div id="particles-js2" class="min-h-screen"></div>
            <div class="mx-auto max-w-screen-md text-center lg:px-2 relative">
                <div class="lg:mb-64">
                    <p class="uppercase font-bold text-2xl lg:text-4xl text-amber-500 mb-0">Inscrições para a Equipe de Trabalho Encerradas!</p>
                </div>

                <div class="flex justify-center">
                    <figure class="flex justify-center items-center mt-20 lg:mt-0 lg:mb-12 rounded">
                        <img src="{{ asset('img/Campfire-bro.svg') }}" alt=""
                             class="w-full h-96 rounded-2xl">
                    </figure>
                </div>
            </div>
        </section>
    @endif
</div>
