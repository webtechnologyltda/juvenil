<section
    class="relative w-full h-[100vh] overflow-hidden bg-gray-900  bg-blend-multiply bg-opacity-25  ">
    <video autoplay="autoplay" autoplay="autoplay" loop muted playsinline preload="auto" class="absolute inset-0 w-full h-full object-cover opacity-80 -z-10 bg-center">
        <source src="{{asset('img/video.mp4')}}" type="video/mp4" />
        Seu navegador não suporta reprodução de videos, tente atualizar para a versão mais recente ou usar
        outro.
    </video>
    <div class="absolute inset-0 bg-black opacity-50 z-0"></div>
    <section class="relative  w-full flex flex-wrap justify-center ">
        <header
            class="container hidden lg:flex justify-between h-[60px]  items-center py-[45px] border-b-[1px] border-gray-700 border-opacity-40">
            <figure class="w-[60px] lg:w-[80px] h-[60px] lg:h-[80px]">
{{--                <img src="{{asset('img/logo_simple.png')}}" class=" mt-4 w-full">--}}
            </figure>
            <nav class="h-[100%] md:w-[70%]">
            </nav>
        </header>
        <!-- header for mobile -->
{{--        <header--}}
{{--            class="w-[80%] sm:w-[70%] md:w-[80%] container flex h-[60px] justify-between  items-center  lg:hidden  py-[45px] relative border-b-[1px] border-gray-700 border-opacity-40 ">--}}
{{--            <figure class="w-[80px]">--}}
{{--                <img src={{asset('img/logo_simple.png')}} alt="" class="w-[100%]">--}}
{{--            </figure>--}}
{{--        </header>--}}
        <!-- end header mobile  -->
    </section>
    <x-home-banner></x-home-banner>
</section>


