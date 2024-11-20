<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Trekking - Politica de Privacidade</title>
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
        function gtag(){dataLayer.push(arguments);}
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

    <img src="{{ asset('img/fundo.jpg') }}" class="top-0 min-h-full object-cover -z-20 absolute opacity-100 hidden md:block">

    {{--    MOBILE--}}
    <img src="{{ asset('img/fundo.jpg') }}" class="top-0 w-full h-full object-cover -z-20 absolute opacity-100 md:hidden">
    {{--    END MOBILE--}}

    <div class="container mx-auto text-black bg-white mt-10 px-8 rounded-xl relative z-10">
        <h1 class="text-3xl font-bold mb-4 pt-8">Política de Privacidade</h1>
        <p class="mb-4">
            A sua privacidade é importante para nós. É política da Web Technology respeitar a sua privacidade em
            relação a qualquer informação sua que possamos coletar no site do Trekking, e outros sites que possuímos
            e operamos.
        </p>

        <h2 class="text-2xl font-semibold mb-2">1. Informações que coletamos</h2>
        <p class="mb-4">
            Coletamos informações pessoais, como nome, endereço de e-mail, número de telefone, gênero, data de nascimento,
            endereço físico, apenas quando você nos fornece voluntariamente. Também coletamos dados de navegação como endereço IP,
            tipo de navegador, páginas acessadas e tempo de navegação para melhorar nossos serviços.
        </p>

        <h2 class="text-2xl font-semibold mb-2">2. Como utilizamos suas informações</h2>
        <p class="mb-4">
            Utilizamos as informações coletadas para personalizar sua experiência no site, oferecer conteúdos
            relevantes, melhorar nosso atendimento ao cliente e para fins de segurança e prevenção de fraude.
        </p>

        <h2 class="text-2xl font-semibold mb-2">3. Compartilhamento de informações</h2>
        <p class="mb-4">
            Não compartilhamos informações pessoalmente identificáveis com terceiros, exceto quando exigido por lei,
            para cumprimento de obrigações legais ou para proteger os direitos e segurança de nossa empresa e usuários.
        </p>

        <h2 class="text-2xl font-semibold mb-2">4. Cookies e tecnologias de rastreamento</h2>
        <p class="mb-4">
            Utilizamos cookies para armazenar informações sobre preferências dos visitantes, registrar informações
            específicas sobre as páginas que os usuários acessam ou visitam e personalizar o conteúdo da página com base
            no tipo de navegador ou outras informações que o visitante envia via navegador.
        </p>

        <h2 class="text-2xl font-semibold mb-2">5. Segurança das informações</h2>
        <p class="mb-4">
            Adotamos medidas de segurança para proteger suas informações pessoais contra perda, roubo e uso não
            autorizado, como criptografia e controles de acesso. No entanto, nenhum sistema de segurança é 100% seguro
            e, portanto, não podemos garantir a total proteção dos seus dados.
        </p>

        <h2 class="text-2xl font-semibold mb-2">6. Links para outros sites</h2>
        <p class="mb-4">
            Nosso site pode conter links para sites externos que não são operados por nós. Não temos controle sobre o
            conteúdo e práticas desses sites e não podemos aceitar responsabilidade por suas respectivas políticas de
            privacidade.
        </p>

        <h2 class="text-2xl font-semibold mb-2">7. Alterações nesta política</h2>
        <p class="mb-4">
            Podemos atualizar nossa Política de Privacidade de tempos em tempos, conforme necessário, para refletir
            alterações em nossas práticas. Recomendamos que você revise esta página periodicamente para se manter
            informado sobre como estamos protegendo suas informações.
        </p>

        <h2 class="text-2xl font-semibold mb-2">8. Contato</h2>
        <p class="pb-8">
            Caso tenha qualquer dúvida sobre esta política de privacidade ou sobre o tratamento de suas informações,
            entre em contato conosco através do e-mail: <a href="mailto:atendimento@webtechnology.com.br" class="text-blue-700">atendimento@webtechnology.com.br</a>.
        </p>
    </div>
</body>
</html>
