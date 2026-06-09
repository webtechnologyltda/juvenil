<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report['title'] }} - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ $logoSrc ?? asset('img/logo.png') }}">
    <style>
        :root {
            --ink: #082529;
            --muted: #5f7276;
            --line: #d9e5e7;
            --panel: #ffffff;
            --accent: #f46b12;
            --soft: #f3f8f9;
            --report-screen-bg: #03181c;
            --report-screen-base: #052f35;
            --report-screen-surface: #073d45;
            --report-screen-panel: #041f23;
            --report-screen-line: rgba(157, 219, 239, .22);
            --report-screen-line-strong: rgba(157, 219, 239, .36);
            --report-screen-sky: #9ddbef;
            --report-screen-text: #f4fbfd;
            --report-screen-muted: #d8f2fa;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            background: var(--report-screen-bg);
            color: var(--report-screen-text);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.45;
        }

        .report-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid var(--report-screen-line);
            background: rgba(3, 24, 28, .96);
            padding: 1rem 1.25rem;
            color: var(--report-screen-text);
        }

        .report-toolbar strong {
            display: block;
            font-size: 1rem;
        }

        .report-toolbar span {
            color: var(--report-screen-muted);
        }

        .report-print-toolbar__eyebrow {
            display: block;
            color: var(--report-screen-sky);
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .report-print-toolbar__actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .5rem;
        }

        .report-print-action,
        .report-toolbar button {
            display: inline-flex;
            min-height: 2.75rem;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            border: 1px solid rgba(244, 107, 18, .72);
            background: var(--accent);
            color: var(--ink);
            cursor: pointer;
            font-weight: 800;
            letter-spacing: .08em;
            padding: .75rem 1.1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .report-print-action--secondary {
            border-color: var(--report-screen-line-strong);
            background: rgba(7, 61, 69, .74);
            color: var(--report-screen-text);
        }

        .report-print-action__icon {
            width: 1rem;
            height: 1rem;
            flex: 0 0 auto;
        }

        .report-shell {
            max-width: 1180px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .report-content {
            display: block;
        }

        .report-section,
        .report-page {
            border: 1px solid var(--line);
            background: var(--panel);
            color: var(--ink);
        }

        .report-heading {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 1rem;
            border: 1px solid var(--report-screen-line);
            background: rgba(7, 61, 69, .82);
            color: var(--report-screen-text);
            margin-bottom: 1rem;
            padding: 1.25rem;
        }

        .report-heading__brand {
            display: flex;
            align-items: center;
            gap: .85rem;
            min-width: 0;
        }

        .report-heading__logo {
            display: block;
            width: 4.25rem;
            height: 4.25rem;
            flex: 0 0 auto;
            object-fit: contain;
        }

        .report-heading h1 {
            margin: 0 0 .35rem;
            color: var(--report-screen-text);
            font-size: 1.7rem;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .report-heading p {
            color: var(--report-screen-muted);
        }

        .report-heading p,
        .report-heading dl,
        .report-heading dd {
            margin: 0;
        }

        .report-heading dt {
            color: var(--report-screen-sky);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .report-heading dd {
            color: var(--report-screen-text);
            font-weight: 700;
        }

        .report-filters {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .report-filter {
            border: 1px solid var(--report-screen-line);
            background: rgba(4, 31, 35, .72);
            color: var(--report-screen-text);
            padding: .75rem;
        }

        .report-filter span {
            display: block;
            color: var(--report-screen-sky);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .report-filter strong {
            display: block;
            margin-top: .2rem;
            color: var(--report-screen-text);
        }

        .report-section {
            margin-bottom: 1rem;
            padding: 1rem;
        }

        .report-section h2,
        .report-page h2,
        .report-card h3 {
            margin: 0;
        }

        .report-page {
            margin-bottom: 1rem;
            padding: 1rem;
            break-after: page;
            page-break-after: always;
        }

        .report-page:last-child {
            break-after: auto;
            page-break-after: auto;
        }

        .report-cover {
            display: grid;
            align-content: start;
            gap: 1rem;
            break-after: page;
            page-break-after: always;
        }

        .report-cover .report-heading,
        .report-cover .report-filters,
        .report-cover .report-sensitive-alert {
            margin-bottom: 0;
        }

        .report-cover__intro {
            border-left: .28rem solid var(--accent);
            padding-left: .85rem;
        }

        .report-cover__intro span {
            color: var(--muted);
            display: block;
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .report-cover__intro h2 {
            margin: .2rem 0 .3rem;
            font-size: 1.15rem;
        }

        .report-cover__intro p {
            color: var(--muted);
            margin: 0;
            max-width: 44rem;
        }

        .report-page__top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 2px solid var(--ink);
            margin-bottom: 1rem;
            padding-bottom: .75rem;
        }

        .report-page__identity {
            display: flex;
            align-items: center;
            gap: .85rem;
            min-width: 0;
        }

        .report-photo {
            display: grid;
            width: 4.5rem;
            aspect-ratio: 1 / 1;
            flex: 0 0 auto;
            place-items: center;
            overflow: hidden;
            border: 1px solid var(--line);
            background: var(--soft);
            color: var(--ink);
            font-size: 1.8rem;
            font-weight: 800;
        }

        .report-photo img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .report-photo__fallback {
            object-fit: contain;
            padding: .65rem;
        }

        .report-badge {
            border: 1px solid var(--line);
            background: var(--soft);
            color: var(--ink);
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .08em;
            padding: .35rem .5rem;
            text-transform: uppercase;
        }

        .report-badge__icon {
            width: .78rem;
            height: .78rem;
            flex: 0 0 auto;
        }

        .report-badge--success {
            border-color: rgba(34, 197, 94, .38);
            background: rgba(34, 197, 94, .1);
            color: #0f6b38;
        }

        .report-badge--warning {
            border-color: rgba(234, 179, 8, .5);
            background: rgba(234, 179, 8, .12);
            color: #7a5200;
        }

        .report-badge--danger {
            border-color: rgba(225, 29, 72, .35);
            background: rgba(225, 29, 72, .08);
            color: #9f1239;
        }

        .report-badge--tribe .report-badge__icon {
            color: var(--report-accent);
        }

        .report-registration-ficha__top {
            align-items: center;
            border-bottom: 0;
            margin-bottom: .75rem;
            padding-bottom: 0;
        }

        .report-registration-ficha__identity h2 {
            margin-top: .25rem;
            line-height: 1.05;
        }

        .report-registration-ficha__meta {
            color: var(--muted);
            font-size: .78rem;
            font-weight: 700;
            margin: .25rem 0 0;
        }

        .report-registration-ficha__meta span::before {
            color: var(--line);
            content: " / ";
            font-weight: 400;
        }

        .report-registration-ficha__badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .35rem;
        }

        .report-registration-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            border: 1px solid var(--line);
            margin-bottom: .75rem;
            background: #f9fcfc;
        }

        .report-registration-summary__item {
            display: grid;
            gap: .28rem;
            min-width: 0;
            border-top: 3px solid var(--report-accent);
            border-right: 1px solid var(--line);
            padding: .6rem .7rem;
        }

        .report-registration-summary__item:last-child {
            border-right: 0;
        }

        .report-registration-summary__item span {
            color: var(--muted);
            font-size: .64rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .report-registration-summary__item strong {
            display: inline-flex;
            min-width: 0;
            align-items: center;
            gap: .35rem;
            color: var(--ink);
            font-size: .86rem;
            line-height: 1.2;
        }

        .report-registration-summary__icon {
            width: .95rem;
            height: .95rem;
            flex: 0 0 auto;
            color: var(--report-accent);
        }

        .report-registration-ficha__sections {
            align-items: start;
        }

        .report-registration-ficha__bento {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .55rem;
            align-items: start;
        }

        .report-registration-ficha .report-card {
            page-break-inside: avoid;
        }

        .report-registration-ficha .report-card h3 {
            display: flex;
            align-items: center;
            gap: .45rem;
        }

        .report-registration-ficha .report-card h3::before {
            display: block;
            width: .26rem;
            height: 1.15rem;
            background: linear-gradient(180deg, var(--accent), #9ddbef);
            content: "";
            flex: 0 0 auto;
        }

        .report-sensitive-badge {
            border: 1px solid rgba(244, 107, 18, .42);
            background: rgba(244, 107, 18, .1);
            color: #9a3f00;
            display: inline-flex;
            align-items: center;
            font-size: .58rem;
            font-weight: 900;
            letter-spacing: .08em;
            line-height: 1;
            padding: .25rem .38rem;
            text-transform: uppercase;
        }

        .report-two-col,
        .report-grid {
            display: grid;
            gap: .75rem;
        }

        .report-two-col {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .report-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .report-card {
            border: 1px solid var(--line);
            background: #fff;
            padding: .85rem;
        }

        .report-card h3 {
            font-size: .95rem;
            margin-bottom: .6rem;
        }

        .report-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .45rem .75rem;
        }

        .report-fields-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .report-fields-table .report-field {
            padding: .12rem .55rem .32rem 0;
            vertical-align: top;
        }

        .report-fields-table .report-field:last-child {
            padding-right: 0;
        }

        .report-field--empty {
            padding: 0;
        }

        .report-field__label,
        .report-fields dt,
        .report-table th {
            color: var(--muted);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .report-field__value,
        .report-fields dd {
            display: block;
            margin: .12rem 0 0;
            font-weight: 700;
            line-height: 1.24;
            word-break: break-word;
        }

        .report-field--wide {
            grid-column: 1 / -1;
        }

        .report-field--tone-warning .report-field__value,
        .report-field--tone-warning dd {
            color: #8a5a00;
        }

        .report-field--tone-success .report-field__value,
        .report-field--tone-success dd {
            color: #0f6b38;
        }

        .report-registration-payment-section {
            margin-top: .75rem;
            page-break-inside: avoid;
        }

        .report-card--payments {
            grid-column: 1 / -1;
            margin-top: 0;
        }

        .report-registration-payments {
            display: grid;
            gap: .6rem;
        }

        .report-registration-payment {
            border: 1px solid var(--line);
            background: #f9fcfc;
            padding: .75rem;
            page-break-inside: avoid;
        }

        .report-registration-payment header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .6rem;
        }

        .report-registration-payment header span,
        .report-registration-payment dt {
            color: var(--muted);
            font-size: .64rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .report-registration-payment header strong {
            display: block;
            margin-top: .12rem;
            font-size: .9rem;
        }

        .report-registration-payment a {
            border: 1px solid rgba(244, 107, 18, .42);
            color: #9a3f00;
            flex: 0 0 auto;
            font-size: .64rem;
            font-weight: 900;
            letter-spacing: .08em;
            padding: .32rem .45rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .report-registration-payment dl {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .55rem;
            margin: 0;
        }

        .report-registration-payment dd {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin: .1rem 0 0;
            font-weight: 800;
        }

        .report-registration-payment__icon {
            width: .92rem;
            height: .92rem;
            flex: 0 0 auto;
            color: var(--report-accent);
        }

        .report-registration-payment-section__empty {
            margin: 0;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        .report-table th,
        .report-table td {
            border: 1px solid var(--line);
            padding: .55rem;
            text-align: left;
            vertical-align: top;
        }

        .report-table tbody tr:nth-child(even) {
            background: #f9fcfc;
        }

        .report-empty {
            border: 1px dashed var(--line);
            color: var(--muted);
            padding: 1rem;
            text-align: center;
        }

        .report-sensitive-alert {
            border: 1px solid rgba(244, 107, 18, .55);
            background: #fff7ed;
            color: #7a2e04;
            margin-bottom: 1rem;
            padding: .85rem 1rem;
        }

        .report-sensitive-alert strong {
            display: block;
            color: #9a3f00;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            margin-bottom: .2rem;
            text-transform: uppercase;
        }

        @media print {
            @page {
                margin: 11mm;
                size: A4;
            }

            body {
                background: #fff;
                color: var(--ink);
                font-size: 11px;
            }

            .report-toolbar,
            .report-print-toolbar {
                display: none;
            }

            .report-shell {
                max-width: none;
                padding: 0;
            }

            .report-cover {
                min-height: calc(297mm - 22mm);
            }

            .report-heading,
            .report-section,
            .report-page,
            .report-card,
            .report-registration-summary,
            .report-registration-summary__item,
            .report-registration-payment,
            .report-filter,
            .report-table th,
            .report-table td {
                border-color: #b7c6c9;
            }

            .report-heading,
            .report-filter,
            .report-sensitive-alert {
                background: #fff;
                color: var(--ink);
            }

            .report-heading h1,
            .report-heading dd,
            .report-filter strong {
                color: var(--ink);
            }

            .report-heading p,
            .report-heading dt,
            .report-filter span,
            .report-cover__intro,
            .report-cover__intro span,
            .report-cover__intro p {
                color: var(--muted);
            }

            .report-registration-payment a {
                display: none;
            }
        }
    </style>
</head>
<body class="report-print-document">
    <div class="report-toolbar report-print-toolbar">
        <div>
            <span class="report-print-toolbar__eyebrow">Prévia para impressão</span>
            <strong>{{ $report['title'] }}</strong>
            <span>{{ $report['recordsCount'] }} registros - gerado em {{ $report['generatedAt'] }}</span>
        </div>
        <div class="report-print-toolbar__actions">
            <a class="report-print-action report-print-action--secondary" href="{{ $returnUrl }}" data-report-action-icon="heroicon-s-arrow-left">
                @svg('heroicon-s-arrow-left', 'report-print-action__icon', ['aria-hidden' => 'true'])
                <span>Voltar para a central</span>
            </a>
            <button class="report-print-action" type="button" data-report-save-pdf data-report-action-icon="heroicon-s-arrow-down-tray" onclick="window.print()">
                @svg('heroicon-s-arrow-down-tray', 'report-print-action__icon', ['aria-hidden' => 'true'])
                <span>Salvar PDF</span>
            </button>
            <button class="report-print-action" type="button" data-report-print data-report-action-icon="heroicon-s-printer" onclick="window.print()">
                @svg('heroicon-s-printer', 'report-print-action__icon', ['aria-hidden' => 'true'])
                <span>Imprimir</span>
            </button>
        </div>
    </div>

    @php
        $isRegistrationFichas = $report['type']->value === 'registration_fichas';
    @endphp

    <main class="report-shell">
        <section
            @class([
                'report-page',
                'report-cover',
                'report-cover--registration-fichas' => $isRegistrationFichas,
            ])
            aria-label="Dados do relatório"
        >
            <header class="report-heading report-print-panel">
                <div class="report-heading__brand">
                    <img class="report-heading__logo" src="{{ $logoSrc ?? asset('img/logo.png') }}" alt="Logo do acampamento">
                    <div>
                        <h1>{{ $report['title'] }}</h1>
                        <p>{{ $report['description'] }}</p>
                    </div>
                </div>
                <dl>
                    <dt>Gerado em</dt>
                    <dd>{{ $report['generatedAt'] }}</dd>
                    <dt>Total</dt>
                    <dd>{{ $report['recordsCount'] }} registros</dd>
                </dl>
            </header>

            <div class="report-cover__intro">
                <span>Dados do relatório</span>
                <h2>Conferência da impressão</h2>
                <p>Confira o tipo de relatório, a quantidade de registros e os filtros aplicados antes de distribuir as fichas.</p>
            </div>

            <section class="report-filters report-cover__filters" aria-label="Filtros aplicados">
                @foreach ($report['filters'] as $label => $value)
                    <div class="report-filter report-print-filter">
                        <span>{{ $label }}</span>
                        <strong>{{ $value ?: 'Não informado' }}</strong>
                    </div>
                @endforeach
            </section>

            @if ($report['showSensitiveHealth'] ?? false)
                <section class="report-sensitive-alert" aria-label="Aviso sobre dados médicos sensíveis">
                    <strong>Dados médicos sensíveis exibidos</strong>
                    Este relatório contém informações de saúde e deve ser tratado com cuidado, sem compartilhamento fora das pessoas responsáveis pelo cuidado e pela operação do acampamento.
                </section>
            @endif
        </section>

        <section
            @class([
                'report-content',
                'report-content--registration-fichas' => $isRegistrationFichas,
            ])
        >
            @switch($report['type']->value)
                @case('registration_fichas')
                    @include('admin.reports.partials.registration-fichas', [
                        'fichas' => $report['fichas'],
                        'showSensitiveHealth' => $report['showSensitiveHealth'] ?? false,
                    ])
                    @break

                @case('tribe_quadrant')
                    @include('admin.reports.partials.tribe-quadrant', ['tribes' => $report['tribes']])
                    @break

                @case('sensitive_health')
                    @include('admin.reports.partials.sensitive-health', ['rows' => $report['medicalRows']])
                    @break

                @case('mission_contacts')
                    @include('admin.reports.partials.mission-contacts', ['rows' => $report['missionRows']])
                    @break
            @endswitch
        </section>
    </main>
</body>
</html>
