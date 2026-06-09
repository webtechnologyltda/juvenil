@forelse ($fichas as $ficha)
    <article class="report-page report-registration-ficha">
        <header class="report-page__top report-registration-ficha__top">
            <div class="report-page__identity report-registration-ficha__identity">
                <div class="report-photo">
                    @if ($ficha['avatar_url'])
                        <img
                            src="{{ $ficha['avatar_url'] }}"
                            alt="Foto de {{ $ficha['name'] }}"
                            onerror="this.onerror=null; this.src='{{ asset('img/logo.png') }}'; this.classList.add('report-photo__fallback');"
                        >
                    @else
                        <span>{{ str($ficha['name'])->substr(0, 1)->upper() }}</span>
                    @endif
                </div>

                <div class="report-registration-ficha__name">
                    <span class="report-badge">Ficha oficial</span>
                    <h2>{{ $ficha['name'] }}</h2>
                    <p class="report-registration-ficha__meta">
                        Inscrição #{{ $ficha['id'] }}
                        @if ($ficha['created_at'])
                            <span>Registrada em {{ $ficha['created_at'] }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="report-registration-ficha__badges">
                <span
                    class="report-badge report-badge--tribe"
                    data-report-badge-icon="heroicon-s-flag"
                    style="--report-accent: {{ $ficha['tribe']['accent'] }};"
                >
                    @svg('heroicon-s-flag', 'report-badge__icon', ['aria-hidden' => 'true'])
                    {{ $ficha['tribe']['label'] }}
                </span>
            </div>
        </header>

        <div @class([
            'report-registration-ficha__bento',
            'report-registration-ficha__bento--with-payments' => $ficha['can_view_payments'],
        ])>
            @foreach ($ficha['sections'] as $section)
                <section class="report-card report-card--{{ $section['area'] }}">
                    <h3>
                        <span>{{ $section['title'] }}</span>
                        @if (($showSensitiveHealth ?? false) && ($section['area'] ?? null) === 'health')
                            <span class="report-sensitive-badge">Dados sensíveis</span>
                        @endif
                    </h3>
                    @php
                        $fieldRows = [];
                        $pendingFields = [];

                        foreach ($section['fields'] as $field) {
                            if ($field['wide'] ?? false) {
                                if ($pendingFields !== []) {
                                    $fieldRows[] = $pendingFields;
                                    $pendingFields = [];
                                }

                                $fieldRows[] = [$field];

                                continue;
                            }

                            $pendingFields[] = $field;

                            if (count($pendingFields) === 2) {
                                $fieldRows[] = $pendingFields;
                                $pendingFields = [];
                            }
                        }

                        if ($pendingFields !== []) {
                            $fieldRows[] = $pendingFields;
                        }
                    @endphp

                    <table class="report-fields-table">
                        <tbody>
                        @foreach ($fieldRows as $row)
                            <tr>
                                @foreach ($row as $field)
                                    <td
                                        @class([
                                            'report-field',
                                            'report-field--wide' => $field['wide'] ?? false,
                                            'report-field--tone-' . ($field['tone'] ?? '') => isset($field['tone']),
                                        ])
                                        @if (count($row) === 1) colspan="2" @endif
                                    >
                                        <span class="report-field__label">{{ $field['label'] }}</span>
                                        <strong class="report-field__value">{{ filled($field['value'] ?? null) ? $field['value'] : 'Não informado' }}</strong>
                                    </td>
                                @endforeach

                                @if (count($row) === 1 && ! ($row[0]['wide'] ?? false))
                                    <td class="report-field report-field--empty"></td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </section>
            @endforeach

            @if ($ficha['can_view_payments'])
                <section class="report-card report-card--payments report-registration-payment-section">
                    <h3>Pagamentos vinculados</h3>

                    @if (count($ficha['payments']))
                        <div class="report-registration-payments">
                            @foreach ($ficha['payments'] as $payment)
                                <article class="report-registration-payment">
                                    <header>
                                        <div>
                                            <span>Lançamento financeiro</span>
                                            <strong>{{ $payment['name'] }}</strong>
                                        </div>

                                        @if ($payment['url'])
                                            <a href="{{ $payment['url'] }}">Visualizar lançamento</a>
                                        @endif
                                    </header>

                                    <dl>
                                        <div>
                                            <dt>Valor aplicado</dt>
                                            <dd>{{ $payment['amount'] }}</dd>
                                        </div>
                                        <div>
                                            <dt>Data</dt>
                                            <dd>{{ $payment['date'] }}</dd>
                                        </div>
                                        <div
                                            class="report-registration-payment__token"
                                            data-report-payment-icon="{{ $payment['method']['icon'] }}"
                                            data-report-payment-color="{{ $payment['method']['color'] }}"
                                            style="--report-accent: {{ $payment['method']['accent'] }};"
                                        >
                                            <dt>Forma</dt>
                                            <dd>
                                                @svg($payment['method']['icon'], 'report-registration-payment__icon', ['aria-hidden' => 'true'])
                                                {{ $payment['method']['label'] }}
                                            </dd>
                                        </div>
                                        <div
                                            class="report-registration-payment__token"
                                            data-report-payment-icon="{{ $payment['status']['icon'] }}"
                                            data-report-payment-color="{{ $payment['status']['color'] }}"
                                            style="--report-accent: {{ $payment['status']['accent'] }};"
                                        >
                                            <dt>Status</dt>
                                            <dd>
                                                @svg($payment['status']['icon'], 'report-registration-payment__icon', ['aria-hidden' => 'true'])
                                                {{ $payment['status']['label'] }}
                                            </dd>
                                        </div>
                                    </dl>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="report-empty report-registration-payment-section__empty">Nenhum lançamento financeiro vinculado a esta inscrição.</p>
                    @endif
                </section>
            @endif
        </div>
    </article>
@empty
    <p class="report-empty">Nenhuma ficha encontrada com os filtros informados.</p>
@endforelse
