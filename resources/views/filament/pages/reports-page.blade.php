<x-filament-panels::page>
    <div class="juvenil-report-page">
        <section class="juvenil-report-brief">
            <div>
                <p class="juvenil-report-brief__eyebrow">Prévia segura para impressão</p>
                <h2>Central de impressão</h2>
                <p>
                    Gere fichas, quadrantes e listas operacionais com filtros por status, tribo, presença e busca.
                    A prévia abre na mesma aba e mantém o retorno para esta central.
                </p>
            </div>

            <div class="juvenil-report-brief__meter" aria-hidden="true">
                <span>{{ count($this->reportTypes()) }}</span>
                <strong>modelos disponíveis</strong>
            </div>
        </section>

        @if (count($this->reportTypes()))
            <div class="juvenil-report-form">
                {{ $this->form }}
            </div>
        @else
            <section class="juvenil-report-empty" aria-label="Nenhum relatório disponível">
                <span>Sem acesso</span>
                <h3>Nenhum relatório disponível</h3>
                <p>Seu perfil ainda não possui permissão para gerar relatórios.</p>
            </section>
        @endif

        <div class="juvenil-report-loading" data-report-preview-loading role="status" aria-live="polite" hidden>
            <div class="juvenil-report-loading__panel">
                <span class="juvenil-report-loading__spinner" aria-hidden="true"></span>
                <strong>Abrindo prévia para impressão</strong>
                <p>Se houver muitos dados, mantenha esta aba aberta até o relatório carregar.</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            const previewLink = event.target.closest('[data-report-preview-link]');

            if (! previewLink || previewLink.getAttribute('aria-disabled') === 'true' || ! previewLink.href) {
                return;
            }

            const loading = document.querySelector('[data-report-preview-loading]');

            if (loading) {
                loading.hidden = false;
            }
        });
    </script>
</x-filament-panels::page>
