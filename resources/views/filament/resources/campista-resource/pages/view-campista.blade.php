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
            <div class="juvenil-registration-card__summary-item juvenil-registration-card__summary-item--{{ $summary['tone'] }}">
                <span>{{ $summary['label'] }}</span>
                <strong>{{ $summary['value'] }}</strong>
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

        <section class="juvenil-registration-card__section juvenil-registration-card__section--documents">
            <div class="juvenil-registration-card__section-header">
                <span></span>
                <h3>Comprovantes anexados</h3>
            </div>

            @if (count($ficha['documents']))
                <div class="juvenil-registration-card__documents">
                    @foreach ($ficha['documents'] as $document)
                        <a href="{{ $document['url'] }}" target="_blank" rel="noopener noreferrer">
                            <span>{{ $document['name'] }}</span>
                            <small>Abrir documento</small>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="juvenil-registration-card__empty">Nenhum comprovante anexado.</p>
            @endif
        </section>
    </div>
</div>
