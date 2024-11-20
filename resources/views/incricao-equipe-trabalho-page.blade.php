<!DOCTYPE html>
<html lang="pt_BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{config('app.name')}}</title>
{{--    <link rel="icon" type="image/png" href="{{ asset('img/logo_light.png') }}">--}}
    @filamentStyles
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="font-primary bg-gray-950 scrollbar-thin scrollbar-track-gray-950 scrollbar-thumb-gray-800 scrollbar-thumb-rounded-full">
@livewire('notifications')
<main class="mx-auto">
    <section
        class="relative w-full h-[100vh] overflow-hidden bg-gray-900  bg-blend-multiply bg-opacity-25  ">
        <video autoplay="autoplay" autoplay="autoplay" loop muted playsinline preload="auto" class="absolute inset-0 w-full h-full object-cover opacity-80 -z-10 bg-center">
            <source src="{{asset('img/barraca.mp4')}}" type="video/mp4" />
            Seu navegador não suporta reprodução de videos, tente atualizar para a versão mais recente ou usar
            outro.
        </video>
        <div class="absolute inset-0 bg-black opacity-50 z-0"></div>
        <section class="relative  w-full flex flex-wrap justify-center ">
            <header
                class="container hidden lg:flex justify-between h-[60px]  items-center py-[45px] border-b-[1px] border-gray-700 border-opacity-40">
                <figure class="w-[60px] lg:w-[80px] h-[60px] lg:h-[80px]">
{{--                    <img src="{{asset('img/logo_simple.png')}}" class=" mt-4 w-full">--}}
                </figure>
                <nav class="h-[100%] md:w-[70%]">
                </nav>
            </header>
            <!-- header for mobile -->
            <header
                class="w-[80%] sm:w-[70%] md:w-[80%] container flex h-[60px] justify-between  items-center  lg:hidden  py-[45px] relative border-b-[1px] border-gray-700 border-opacity-40 ">
                <figure class="w-[80px]">
{{--                    <img src={{asset('img/logo_simple.png')}} alt="" class="w-[100%]">--}}
                </figure>
            </header>
            <!-- end header mobile  -->
        </section>
        <section class="relative  w-full flex justify-center mt-[120px]">
            <div class="w-[700px] md:w-[1000px] container h-auto  ">
                <h1 class="w-full text-center text-white md:text-md font-secondary  ">
                    <h1 class="w-full  text-center text-white text-md md:text-[68px] font-secondary  ">
                        <span class=" text-white font-mono font-bold text-4xl md:text-7xl">Acampamento</span>
                    </h1>.
                </h1>
                <h1 class="w-full text-center text-white md:text-md font-secondary  ">
                    <h1 class="w-full  text-center text-white text-md md:text-[68px] font-secondary  ">
                        <span class=" text-color1 font-mono font-bold text-3xl md:text-7xl">Juvenil Senior</span>
                    </h1>.
                </h1>
                <h1 class="w-full text-center text-white md:text-md font-secondary  ">
                    <h1 class="w-full mt-20 text-center text-white text-md md:text-md font-secondary  ">
                        <span class="font-bold text-color1 font-mono  ">Inscrição para equipe de trabalho</span>
                    </h1>.
                </h1>
{{--                <p class="w-full mt-2 text-center font-bold uppercase text-sm text-white tracking-widest [word-spacing:8px]">--}}
{{--                    ¨Viver na santidade também é ser radical¨ João Paulo II</p>--}}


                <p class="w-full mt-2 text-center uppercase  font-bold text-sm text-white tracking-widest [word-spacing:8px] mb-4"> Dias 26 à 30 de Dezembro</p>


                <div class="mt-8 grid justify-items-center">
                    <a
                        href="#registration-work-group" role="button"
                        class=" bg-color3 rounded mt-8 mb-8 p-2 w-[50%]  hover:text-white text-gray-950
                                   flex items-center justify-center text-[12px] hover:bg-amber-700
                                   transition-all duration-500">
                        <span class="relative text-sm  text-center font-mono uppercase">Increver-se para Trabalhar</span>
                    </a>
                </div>
            </div>


        </section>
    </section>
    <section id="registration-work-group" class="max-w-7xl relative mx-auto">
        @livewire('equipe-trabalho-form')
    </section>
</main>

@include('components.footer')

@filamentScripts
@livewireScripts
<script src="{{asset('js/jquery-min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
</body>
</html>
