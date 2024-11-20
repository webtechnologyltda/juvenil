<!DOCTYPE html>
<html>
<head>
    <title>{{config('app.name')}} - Permissão Negada</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid content-center isolate min-h-screen h-screen w-screen bg-black">
<img src="{{asset('img/errors/403.jpg')}}" alt=""
     class="absolute inset-0 -z-10 h-full w-full object-cover object-center opacity-20 lg:opacity-25">
<div class="mx-auto  max-w-7xl px-6 py-32 text-center sm:py-40 lg:px-8">
    <p class="text-5xl lg:text-2xl font-extrabold leading-8 text-color1">403</p>
    <h1 class="mt-4 text-6xl lg:text-3xl font-bold tracking-tight text-white">Área 51 - Nível de acesso exigido</h1>
    <p class="text-4xl lg:text-base mt-4 text-white/70 sm:mt-6">Parece que você não tem nível de acesso para este local,
        tente novamente ou contate um administrador para mais detalhes.</p>
    <div class="mt-10 flex justify-center">
        <a href="{{route('welcome')}}"
           class="text-2xl lg:text-sm leading-7 bg-color1 p-4 lg:p-2 rounded text-gray-950 hover:font-bold">
            <i class="bi-arrow-repeat" aria-hidden="true"></i>
            Tentar novamente</a>
    </div>
</div>
</body>
</html>
