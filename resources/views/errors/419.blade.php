<!DOCTYPE html>
<html>
<head>
    <title>{{config('app.name')}} - Sessão Expirada</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid content-center isolate min-h-screen h-screen w-screen bg-black">
<img src="{{asset('img/errors/419.jpg')}}" alt=""
     class="absolute inset-0 -z-10 h-full w-full object-cover object-center opacity-30 lg:opacity-30">
<div class="mx-auto  max-w-7xl px-6 py-32 text-center sm:py-40 lg:px-8">
    <p class="text-5xl lg:text-2xl font-extrabold leading-8 text-color1">419</p>
    <h1 class="mt-4 text-6xl lg:text-3xl font-bold tracking-tight text-white">Ops! Sua sessão expirou</h1>
    <p class="text-4xl lg:text-base mt-4 text-white/70 sm:mt-6">Desculpe o transtorno, sua sessão expirou. Por favor tente novamente ou volte mais tarde.</p>
    <div class="mt-10 flex justify-center">
        <a href="{{route('welcome')}}"
           class="text-2xl lg:text-sm leading-7 bg-color1 p-4 lg:p-2 rounded text-gray-950 hover:font-bold">
            <i class="bi-arrow-repeat" aria-hidden="true"></i>
            Tentar novamente</a>
    </div>
</div>
</body>
</html>
