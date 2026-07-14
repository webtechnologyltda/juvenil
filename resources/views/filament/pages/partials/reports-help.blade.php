@php
    $availableValues = collect($types)->pluck('value')->all();

    $reportHelp = collect([
        'usage' => [
            'title' => 'Como usar a central de relatórios',
            'tone' => 'operacional',
            'description' => 'Escolha o tipo de relatório, aplique status, tribo, presença e busca, revise os controles sensíveis quando aparecerem e abra a prévia. A tela de prévia mantém um link de volta para a central com os mesmos filtros.',
        ],
        'registration_fichas' => [
            'title' => 'Fichas de inscrição',
            'tone' => 'operacional',
            'description' => 'Use para imprimir uma ficha consolidada por campista, com dados pessoais, contato, endereço, comunidade, controle da inscrição e campos médicos ocultos por padrão.',
        ],
        'tribe_quadrant' => [
            'title' => 'Quadrante das inscrições por tribo',
            'tone' => 'operacional',
            'description' => 'Use para conferir a distribuição dos campistas por tribo, validar agrupamentos e apoiar organização de equipes e espaços.',
        ],
        'sensitive_health' => [
            'title' => 'Lista médica da enfermaria',
            'tone' => 'restrito',
            'description' => 'Use somente para triagem e cuidado da enfermaria. Os detalhes médicos aparecem apenas para perfis autorizados e após confirmação explícita.',
        ],
        'mission_contacts' => [
            'title' => 'Contatos e endereços para missão',
            'tone' => 'operacional',
            'description' => 'Use para preparar visitas missionárias com responsável, telefone, endereço, bairro, cidade e ponto de referência.',
        ],
        'registration_payments' => [
            'title' => 'Pagamentos de campistas e equipe de trabalho',
            'tone' => 'financeiro',
            'description' => 'Use para conferir, em uma única lista, os lançamentos pagos ou pendentes vinculados a campistas e integrantes da equipe de trabalho.',
        ],
    ])
        ->filter(fn (array $item, string $key): bool => $key === 'usage' || in_array($key, $availableValues, true));
@endphp

<div class="juvenil-report-help">
    <ul class="juvenil-report-help__list">
        @foreach ($reportHelp as $key => $item)
            <li @class([
                'juvenil-report-help__item',
                'juvenil-report-help__item--restricted' => $item['tone'] === 'restrito',
            ])>
                <span>{{ $item['tone'] }}</span>
                <strong>{{ $item['title'] }}</strong>
                <p>{{ $item['description'] }}</p>
            </li>
        @endforeach
    </ul>
</div>
