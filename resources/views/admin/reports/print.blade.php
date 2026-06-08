<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report['title'] }}</title>
    <style>
        :root {
            --ink: #082529;
            --muted: #5f7276;
            --line: #d9e5e7;
            --panel: #ffffff;
            --accent: #f46b12;
            --soft: #f3f8f9;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--soft);
            color: var(--ink);
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
            border-bottom: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.94);
            padding: 1rem 1.25rem;
        }

        .report-toolbar strong {
            display: block;
            font-size: 1rem;
        }

        .report-toolbar span {
            color: var(--muted);
        }

        .report-toolbar button {
            min-height: 2.75rem;
            border: 0;
            background: var(--accent);
            color: #082529;
            cursor: pointer;
            font-weight: 800;
            letter-spacing: .08em;
            padding: .75rem 1.1rem;
            text-transform: uppercase;
        }

        .report-shell {
            max-width: 1180px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .report-heading,
        .report-section,
        .report-page {
            border: 1px solid var(--line);
            background: var(--panel);
        }

        .report-heading {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 1rem;
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
            font-size: 1.7rem;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .report-heading p,
        .report-heading dl,
        .report-heading dd {
            margin: 0;
        }

        .report-heading dt {
            color: var(--muted);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .report-heading dd {
            font-weight: 700;
        }

        .report-filters {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: 1rem;
        }

        .report-filter {
            border: 1px solid var(--line);
            background: #fff;
            padding: .75rem;
        }

        .report-filter span {
            display: block;
            color: var(--muted);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .report-filter strong {
            display: block;
            margin-top: .2rem;
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
            page-break-after: always;
        }

        .report-page:last-child {
            page-break-after: auto;
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
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .08em;
            padding: .35rem .5rem;
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

        .report-fields dt,
        .report-table th {
            color: var(--muted);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .report-fields dd {
            margin: .12rem 0 0;
            font-weight: 700;
            word-break: break-word;
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
            background: rgba(244, 107, 18, .12);
            color: var(--ink);
            margin-bottom: 1rem;
            padding: .85rem 1rem;
        }

        .report-sensitive-alert strong {
            display: block;
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
                font-size: 11px;
            }

            .report-toolbar {
                display: none;
            }

            .report-shell {
                max-width: none;
                padding: 0;
            }

            .report-heading,
            .report-section,
            .report-page,
            .report-card,
            .report-filter,
            .report-table th,
            .report-table td {
                border-color: #b7c6c9;
            }
        }
    </style>
</head>
<body>
    <div class="report-toolbar">
        <div>
            <strong>{{ $report['title'] }}</strong>
            <span>{{ $report['recordsCount'] }} registros - gerado em {{ $report['generatedAt'] }}</span>
        </div>
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <main class="report-shell">
        <header class="report-heading">
            <div class="report-heading__brand">
                <img class="report-heading__logo" src="{{ asset('img/logo.png') }}" alt="Logo do acampamento">
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

        <section class="report-filters" aria-label="Filtros aplicados">
            @foreach ($report['filters'] as $label => $value)
                <div class="report-filter">
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

        @switch($report['type']->value)
            @case('registration_fichas')
                @include('admin.reports.partials.registration-fichas', ['fichas' => $report['fichas']])
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
    </main>
</body>
</html>
