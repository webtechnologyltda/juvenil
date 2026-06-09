<div class="juvenil-registration-card">
    <header class="juvenil-registration-card__hero">
        <div class="juvenil-registration-card__photo">
            @if ($ficha['avatar_url'])
                <img
                    src="{{ $ficha['avatar_url'] }}"
                    alt="Foto de {{ $ficha['name'] }}"
                    onerror="this.onerror=null; this.src='{{ asset('img/logo.png') }}'; this.classList.add('juvenil-registration-card__photo-fallback');"
                >
            @else
                <span>{{ str($ficha['name'])->substr(0, 1)->upper() }}</span>
            @endif
        </div>

        <div class="juvenil-registration-card__identity">
            <p class="juvenil-registration-card__eyebrow">Ficha oficial</p>
            <div class="juvenil-registration-card__name-row">
                <h2>{{ $ficha['name'] }}</h2>
                <span class="juvenil-registration-card__badge juvenil-registration-card__badge--{{ $ficha['status']['tone'] }}">
                    {{ $ficha['status']['label'] }}
                </span>
            </div>
            <p class="juvenil-registration-card__meta">
                Inscrição #{{ $ficha['id'] }}
                @if ($ficha['created_at'])
                    <span>Registrada em {{ $ficha['created_at'] }}</span>
                @endif
            </p>
        </div>
    </header>

    <div class="juvenil-registration-card__summary" aria-label="Resumo da inscrição">
        @foreach ($ficha['summary'] as $summary)
            <div
                class="juvenil-registration-card__summary-item juvenil-registration-card__summary-item--{{ $summary['tone'] }}"
                @if (filled($summary['icon'] ?? null)) data-summary-icon="{{ $summary['icon'] }}" @endif
                @if (filled($summary['color'] ?? null)) data-summary-color="{{ $summary['color'] }}" @endif
                @style([
                    '--summary-accent: ' . ($summary['accent'] ?? '') => filled($summary['accent'] ?? null),
                ])
            >
                <div class="juvenil-registration-card__summary-heading">
                    <span class="juvenil-registration-card__summary-label">{{ $summary['label'] }}</span>
                </div>

                <strong>
                    @svg($summary['icon'], 'juvenil-registration-card__summary-badge-icon', ['aria-hidden' => 'true'])
                    {{ $summary['value'] }}
                </strong>
            </div>
        @endforeach
    </div>

    <div class="juvenil-registration-card__sections">
        @foreach ($ficha['sections'] as $section)
            <section class="juvenil-registration-card__section">
                <div class="juvenil-registration-card__section-header">
                    <span></span>
                    <h3>{{ $section['title'] }}</h3>
                </div>

                <dl class="juvenil-registration-card__fields">
                    @foreach ($section['fields'] as $field)
                        <div @class([
                            'juvenil-registration-card__field',
                            'juvenil-registration-card__field--wide' => $field['wide'] ?? false,
                            'juvenil-registration-card__field--tone-' . ($field['tone'] ?? '') => isset($field['tone']),
                        ])>
                            <dt>{{ $field['label'] }}</dt>
                            <dd>{{ filled($field['value'] ?? null) ? $field['value'] : 'Não informado' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endforeach

        @if ($ficha['can_view_payments'])
            <section class="juvenil-registration-card__section juvenil-registration-card__section--payments">
                <div class="juvenil-registration-card__section-header">
                    <span></span>
                    <h3>Pagamentos vinculados</h3>
                </div>

                @if (count($ficha['payments']))
                    <div class="juvenil-registration-card__payments">
                        @foreach ($ficha['payments'] as $payment)
                            <article class="juvenil-registration-card__payment">
                                <div class="juvenil-registration-card__payment-header">
                                    <div>
                                        <p>Lançamento financeiro</p>
                                        <strong>{{ $payment['name'] }}</strong>
                                    </div>

                                    @if ($payment['url'])
                                        <a href="{{ $payment['url'] }}">
                                            Visualizar lançamento
                                        </a>
                                    @endif
                                </div>

                                <dl class="juvenil-registration-card__payment-fields">
                                    <div>
                                        <dt>Valor aplicado</dt>
                                        <dd>{{ $payment['amount'] }}</dd>
                                    </div>
                                    <div>
                                        <dt>Data</dt>
                                        <dd>{{ $payment['date'] }}</dd>
                                    </div>
                                    <div
                                        class="juvenil-registration-card__payment-field juvenil-registration-card__payment-field--with-icon"
                                        data-payment-icon="{{ $payment['method']['icon'] }}"
                                        data-payment-color="{{ $payment['method']['color'] }}"
                                        @style([
                                            '--payment-accent: ' . $payment['method']['accent'],
                                        ])
                                    >
                                        <dt>Forma</dt>
                                        <dd>
                                            <span class="juvenil-registration-card__payment-field-icon">
                                                @svg($payment['method']['icon'], 'juvenil-registration-card__payment-field-icon-svg', ['aria-hidden' => 'true'])
                                            </span>
                                            <span>{{ $payment['method']['label'] }}</span>
                                        </dd>
                                    </div>
                                    <div
                                        class="juvenil-registration-card__payment-field juvenil-registration-card__payment-field--with-icon"
                                        data-payment-icon="{{ $payment['status']['icon'] }}"
                                        data-payment-color="{{ $payment['status']['color'] }}"
                                        @style([
                                            '--payment-accent: ' . $payment['status']['accent'],
                                        ])
                                    >
                                        <dt>Status</dt>
                                        <dd>
                                            <span class="juvenil-registration-card__payment-field-icon">
                                                @svg($payment['status']['icon'], 'juvenil-registration-card__payment-field-icon-svg', ['aria-hidden' => 'true'])
                                            </span>
                                            <span>{{ $payment['status']['label'] }}</span>
                                        </dd>
                                    </div>
                                </dl>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="juvenil-registration-card__empty">Nenhum lançamento financeiro vinculado a esta inscrição.</p>
                @endif
            </section>
        @endif

    </div>
</div>
