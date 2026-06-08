@forelse ($fichas as $ficha)
    <article class="report-page">
        <div class="report-page__top">
            <div class="report-page__identity">
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
                </div>
            </div>
            <div>
                <span class="report-badge">Inscrição #{{ $ficha['id'] }}</span>
                <span class="report-badge">{{ $ficha['status'] }}</span>
                <span class="report-badge">{{ $ficha['tribe'] }}</span>
            </div>
        </div>

        <div class="report-two-col">
            @foreach ($ficha['sections'] as $section)
                <section class="report-card">
                    <h3>{{ $section['title'] }}</h3>
                    <dl class="report-fields">
                        @foreach ($section['fields'] as $field)
                            <div>
                                <dt>{{ $field['label'] }}</dt>
                                <dd>{{ filled($field['value'] ?? null) ? $field['value'] : 'Não informado' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </div>
    </article>
@empty
    <p class="report-empty">Nenhuma ficha encontrada com os filtros informados.</p>
@endforelse
