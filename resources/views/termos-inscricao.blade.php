<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Trekking- Termo de Inscrição</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ url(asset('img/logo_simple.png')) }}?20231011">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Holtwood+One+SC&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Holtwood+One+SC&display=swap" rel="stylesheet">

    @filamentStyles
    @livewireStyles

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-W7WHMPQ4CM"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        gtag('config', 'G-W7WHMPQ4CM');
    </script>
</head>
<body class="bg-pink-600 scrollbar-thin scrollbar-track-gray-950 scrollbar-thumb-gray-800 scrollbar-thumb-rounded-full">

<div class="preloader">
    <div class="loader">
        <div class="ytp-spinner">
            <div class="ytp-spinner-container">
                <div class="ytp-spinner-rotator">
                    <div class="ytp-spinner-left">
                        <div class="ytp-spinner-circle"></div>
                    </div>
                    <div class="ytp-spinner-right">
                        <div class="ytp-spinner-circle"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<img src="{{ asset('img/fundo.jpg') }}"
     class="top-0 object-cover -z-20 absolute opacity-100 hidden md:block">

{{--    MOBILE--}}
<img src="{{ asset('img/fundo.jpg') }}"
     class="top-0 w-full h-full object-cover -z-20 absolute opacity-100 md:hidden">
{{--    END MOBILE--}}

<div class="container mx-auto text-black bg-white mt-10 px-8 rounded-xl relative z-10">
    <h1 class="text-3xl font-bold mb-4">Termo de Inscrição</h1>
    <p class="mb-4">
        Agradecemos o seu interesse e apoio ao acampamento Trekking. É importante esclarecer que, ao realizar a inscrição, você está contribuindo financeiramente para a realização do acampamento, ajudando a cobrir despesas como organização, infraestrutura e materiais necessários para a sua execução.
    </p>

    <h2 class="text-2xl font-semibold mb-2">1. Natureza de Doação</h2>
    <p class="mb-4">
        Os valores pagos no ato da inscrição possuem caráter de doação e são destinados integralmente para a organização e execução do acampamento. Dessa forma, ao efetuar o pagamento, você confirma sua intenção de apoiar o evento e contribuir para seu sucesso.
    </p>

    <h2 class="text-2xl font-semibold mb-2">2. Política de Não Devolução</h2>
    <p class="mb-4">
        Considerando que os valores pagos são tratados como doações, não realizamos a devolução dos montantes, mesmo em casos de desistência ou impossibilidade de participação. Isso ocorre porque os recursos já estão alocados em compromissos financeiros que garantem a realização do acampamento.
    </p>

    <h2 class="text-2xl font-semibold mb-2">3. Compromisso com o Evento</h2>
    <p class="mb-4">
        Ao se inscrever, você está assumindo o compromisso de apoiar a execução do acampamento e compreende que sua contribuição ajuda a tornar possível a realização das atividades planejadas para todos os participantes. Mesmo que você não possa comparecer, sua inscrição permite que outros tenham a oportunidade de participar e desfrutar de uma experiência completa.
    </p>

    <h2 class="text-2xl font-semibold mb-2">4. Exceções</h2>
    <p class="mb-4">
        Em casos excepcionais, como cancelamento total do acampamento por parte dos organizadores, estudaremos formas de reembolso ou realocação dos valores para outras atividades ou eventos futuros. No entanto, cada caso será analisado individualmente, respeitando as condições estabelecidas previamente.
    </p>

    <h2 class="text-2xl font-semibold mb-2">5. Contato</h2>
    <p class="mb-4">
        Se você tiver dúvidas sobre esta política, entre em contato com nossa equipe organizadora pelo e-mail <a href="mailto:paroquiasaodomingos.carmo@gmail.com" class="text-blue-500">paroquiasaodomingos.carmo@gmail.com</a>. Teremos o prazer de esclarecer quaisquer questões e proporcionar mais informações sobre como sua contribuição está sendo utilizada.
    </p>

    <p class="mt-8 text-md text-center text-gray-600 pb-10">
        Esta política de não devolução é válida para todas as inscrições realizadas no evento Trekking e está sujeita a alterações sem aviso prévio, conforme necessário para garantir a melhor execução do acampamento.
    </p>
</div>
</body>
</html>
