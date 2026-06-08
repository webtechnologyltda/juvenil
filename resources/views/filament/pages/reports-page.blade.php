<x-filament-panels::page>
    <div class="juvenil-report-page">
        <section class="juvenil-report-brief">
            <div>
                <p class="juvenil-report-brief__eyebrow">Prévia segura para impressão</p>
                <h2>Central de impressão</h2>
                <p>
                    Gere fichas, quadrantes e listas operacionais com filtros por status, tribo,
                    presença e busca. Relatórios sensíveis só aparecem para perfis autorizados.
                </p>
            </div>
        </section>

        <section class="juvenil-report-grid" aria-label="Tipos de relatório disponíveis">
            @forelse ($this->reportTypes() as $type)
                <article @class([
                    'juvenil-report-card',
                    'juvenil-report-card--sensitive' => $type['sensitive'],
                ])>
                    <span>{{ $type['sensitive'] ? 'Restrito' : 'Operacional' }}</span>
                    <h3>{{ $type['title'] }}</h3>
                    <p>{{ $type['description'] }}</p>
                </article>
            @empty
                <article class="juvenil-report-card">
                    <span>Sem acesso</span>
                    <h3>Nenhum relatório disponível</h3>
                    <p>Seu perfil ainda não possui permissão para gerar relatórios.</p>
                </article>
            @endforelse
        </section>

        @if (count($this->reportTypes()))
            <div class="juvenil-report-form">
                {{ $this->form }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
