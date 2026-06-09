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

                <div>
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
                <span class="report-badge report-badge--{{ $ficha['status']['tone'] }}">
                    @svg($ficha['status']['icon'], 'report-badge__icon', ['aria-hidden' => 'true'])
                    {{ $ficha['status']['label'] }}
                </span>
                <span
                    class="report-badge report-badge--tribe"
                    data-report-summary-icon="heroicon-o-flag"
                    style="--report-accent: {{ $ficha['tribe']['accent'] }};"
                >
                    @svg('heroicon-o-flag', 'report-badge__icon', ['aria-hidden' => 'true'])
                    {{ $ficha['tribe']['label'] }}
                </span>
            </div>
        </header>

        <section class="report-registration-summary" aria-label="Resumo da inscrição">
            @foreach ($ficha['summary'] as $summary)
                <div
                    class="report-registration-summary__item report-registration-summary__item--{{ $summary['tone'] }}"
                    data-report-summary-icon="{{ $summary['icon'] }}"
                    data-report-summary-color="{{ $summary['color'] }}"
                    style="--report-accent: {{ $summary['accent'] }};"
                >
                    <span>{{ $summary['label'] }}</span>
                    <strong>
                        @svg($summary['icon'], 'report-registration-summary__icon', ['aria-hidden' => 'true'])
                        {{ $summary['value'] }}
                    </strong>
                </div>
            @endforeach
        </section>

        <div class="report-two-col report-registration-ficha__sections">
            @foreach ($ficha['sections'] as $section)
                <section class="report-card">
                    <h3>{{ $section['title'] }}</h3>
                    <dl class="report-fields">
                        @foreach ($section['fields'] as $field)
                            <div @class([
                                'report-field',
                                'report-field--wide' => $field['wide'] ?? false,
                                'report-field--tone-' . ($field['tone'] ?? '') => isset($field['tone']),
                            ])>
                                <dt>{{ $field['label'] }}</dt>
                                <dd>{{ filled($field['value'] ?? null) ? $field['value'] : 'Não informado' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </div>

        @if ($ficha['can_view_payments'])
            <section class="report-card report-registration-payment-section">
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
    </article>
@empty
    <p class="report-empty">Nenhuma ficha encontrada com os filtros informados.</p>
@endforelse
