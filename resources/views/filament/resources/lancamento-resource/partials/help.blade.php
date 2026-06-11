@once
    <style>
        .juvenil-lancamento-help {
            --help-bg: #041f23;
            --help-panel: rgba(7, 61, 69, .82);
            --help-panel-soft: rgba(20, 78, 86, .68);
            --help-line: rgba(157, 219, 239, .24);
            --help-line-strong: rgba(157, 219, 239, .42);
            --help-text: #f4fbfd;
            --help-muted: #d8f2fa;
            --help-sky: #9ddbef;
            --help-accent: #f46b12;
            display: grid;
            gap: 1rem;
            color: var(--help-text);
        }

        .juvenil-lancamento-help * {
            box-sizing: border-box;
        }

        .juvenil-lancamento-help__hero,
        .juvenil-lancamento-help__panel,
        .juvenil-lancamento-help__table-wrap,
        .juvenil-lancamento-help__figure {
            border: 1px solid var(--help-line);
            background: linear-gradient(135deg, rgba(7, 61, 69, .88), rgba(4, 31, 35, .96));
        }

        .juvenil-lancamento-help__hero {
            display: grid;
            gap: .8rem;
            padding: 1.1rem;
        }

        .juvenil-lancamento-help__eyebrow,
        .juvenil-lancamento-help__step,
        .juvenil-lancamento-help th {
            color: var(--help-sky);
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .juvenil-lancamento-help h2,
        .juvenil-lancamento-help h3,
        .juvenil-lancamento-help p {
            margin: 0;
        }

        .juvenil-lancamento-help h2 {
            font-size: 1.45rem;
            line-height: 1.1;
        }

        .juvenil-lancamento-help h3 {
            font-size: 1rem;
            line-height: 1.2;
        }

        .juvenil-lancamento-help p,
        .juvenil-lancamento-help li,
        .juvenil-lancamento-help td {
            color: var(--help-muted);
            line-height: 1.55;
        }

        .juvenil-lancamento-help strong {
            color: var(--help-text);
        }

        .juvenil-lancamento-help__badges,
        .juvenil-lancamento-help__grid,
        .juvenil-lancamento-help__steps {
            display: grid;
            gap: .75rem;
        }

        .juvenil-lancamento-help__badges {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .juvenil-lancamento-help__badge {
            border: 1px solid var(--help-line);
            background: rgba(4, 31, 35, .72);
            padding: .75rem;
        }

        .juvenil-lancamento-help__badge span {
            display: block;
            color: var(--help-sky);
            font-size: .66rem;
            font-weight: 900;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .juvenil-lancamento-help__badge strong {
            display: block;
            margin-top: .2rem;
        }

        .juvenil-lancamento-help__panel {
            padding: 1rem;
        }

        .juvenil-lancamento-help__grid {
            grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr);
            align-items: start;
        }

        .juvenil-lancamento-help__figure {
            margin: 0;
            overflow: hidden;
        }

        .juvenil-lancamento-help__figure img {
            display: block;
            width: 100%;
            height: auto;
            background: #052f35;
        }

        .juvenil-lancamento-help__figure figcaption {
            border-top: 1px solid var(--help-line);
            color: var(--help-muted);
            font-size: .8rem;
            padding: .65rem .8rem;
        }

        .juvenil-lancamento-help__steps {
            counter-reset: launch-help-step;
        }

        .juvenil-lancamento-help__step-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: .75rem;
            border-left: .22rem solid var(--help-accent);
            background: rgba(4, 31, 35, .58);
            padding: .85rem;
        }

        .juvenil-lancamento-help__step-card::before {
            counter-increment: launch-help-step;
            content: counter(launch-help-step);
            display: grid;
            width: 1.85rem;
            height: 1.85rem;
            place-items: center;
            background: var(--help-accent);
            color: #082529;
            font-weight: 900;
        }

        .juvenil-lancamento-help__callout {
            border: 1px solid rgba(244, 107, 18, .44);
            background: rgba(244, 107, 18, .12);
            padding: .9rem;
        }

        .juvenil-lancamento-help__table-wrap {
            overflow: auto;
        }

        .juvenil-lancamento-help table {
            width: 100%;
            border-collapse: collapse;
            min-width: 42rem;
        }

        .juvenil-lancamento-help th,
        .juvenil-lancamento-help td {
            border-bottom: 1px solid var(--help-line);
            padding: .72rem .8rem;
            text-align: left;
            vertical-align: top;
        }

        .juvenil-lancamento-help tbody tr:last-child td {
            border-bottom: 0;
        }

        .juvenil-lancamento-help__list {
            margin: .7rem 0 0;
            padding-left: 1rem;
        }

        .juvenil-lancamento-help__split {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .juvenil-lancamento-help__table-section {
            display: grid;
            gap: .75rem;
        }

        @media (max-width: 720px) {
            .juvenil-lancamento-help__badges,
            .juvenil-lancamento-help__grid,
            .juvenil-lancamento-help__split {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endonce

<article class="juvenil-lancamento-help">
    <section class="juvenil-lancamento-help__hero">
        <span class="juvenil-lancamento-help__eyebrow">Guia visual</span>
        <h2>Como editar um lançamento financeiro</h2>
        <p>
            Use esta tela para registrar o movimento financeiro, quebrar o valor em itens,
            vincular inscrições quando houver pagamento de campista ou equipe, e anexar os comprovantes.
        </p>

        <div class="juvenil-lancamento-help__badges">
            <div class="juvenil-lancamento-help__badge">
                <span>Valor total</span>
                <strong>Soma dos itens</strong>
            </div>
            <div class="juvenil-lancamento-help__badge">
                <span>Vínculo</span>
                <strong>Opcional por item</strong>
            </div>
            <div class="juvenil-lancamento-help__badge">
                <span>Comprovante</span>
                <strong>Imagem ou PDF</strong>
            </div>
        </div>
    </section>

    <section class="juvenil-lancamento-help__grid">
        <div class="juvenil-lancamento-help__panel">
            <span class="juvenil-lancamento-help__step">Mapa da tela</span>
            <h3>Preencha de cima para baixo</h3>
            <ul class="juvenil-lancamento-help__list">
                <li><strong>Lançamento</strong> define nome, data, status, forma de pagamento e tipo.</li>
                <li><strong>Itens do lançamento</strong> define o valor real e as categorias contábeis.</li>
                <li><strong>Comprovantes</strong> guarda recibos, PIX, notas ou imagens de apoio.</li>
            </ul>
        </div>

        <figure class="juvenil-lancamento-help__figure">
            <img src="{{ asset('img/docs/lancamento-help-overview.svg') }}" alt="Mapa visual da tela de lançamento com cabeçalho, itens e comprovantes">
            <figcaption>Visão geral da tela: dados do lançamento acima, itens e comprovantes abaixo.</figcaption>
        </figure>
    </section>

    <section class="juvenil-lancamento-help__grid">
        <figure class="juvenil-lancamento-help__figure">
            <img src="{{ asset('img/docs/lancamento-help-items.svg') }}" alt="Exemplo visual de um item de lançamento com valor, categoria e vínculo">
            <figcaption>Itens podem ser avulsos ou vinculados a uma inscrição de campista/equipe.</figcaption>
        </figure>

        <div class="juvenil-lancamento-help__panel">
            <span class="juvenil-lancamento-help__step">Passo a passo</span>
            <h3>Como lançar corretamente</h3>
            <div class="juvenil-lancamento-help__steps">
                <div class="juvenil-lancamento-help__step-card">
                    <div>
                        <strong>Escolha o tipo</strong>
                        <p>Use <strong>Receita</strong> para entradas, <strong>Despesa</strong> para saídas e <strong>Doação</strong> quando o dinheiro entrou sem uma inscrição associada.</p>
                    </div>
                </div>
                <div class="juvenil-lancamento-help__step-card">
                    <div>
                        <strong>Separe o valor em itens</strong>
                        <p>Cada item precisa de nome, valor e categoria. O valor do lançamento é calculado pelos itens, então ajuste os itens antes de salvar.</p>
                    </div>
                </div>
                <div class="juvenil-lancamento-help__step-card">
                    <div>
                        <strong>Vincule a inscrição quando existir</strong>
                        <p>Para pagamentos de inscrição, selecione o tipo da inscrição e clique em <strong>Selecionar inscrição</strong>. O sistema preenche o nome do item com a pessoa escolhida.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="juvenil-lancamento-help__table-section" aria-label="Exemplos de preenchimento">
        <div class="juvenil-lancamento-help__panel">
            <span class="juvenil-lancamento-help__step">Exemplos de preenchimento</span>
            <h3>Escolha o tipo conforme a movimentação</h3>
        </div>

        <div class="juvenil-lancamento-help__table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Situação</th>
                        <th>Tipo</th>
                        <th>Item recomendado</th>
                        <th>Vínculo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Campista pagou a inscrição via PIX</td>
                        <td>Receita</td>
                        <td>Nome do campista, categoria Inscrição, valor pago</td>
                        <td>Vincule ao campista</td>
                    </tr>
                    <tr>
                        <td>Compra de mercado para o acampamento</td>
                        <td>Despesa</td>
                        <td>Mercado, categoria Alimentação, valor da nota</td>
                        <td>Sem vínculo</td>
                    </tr>
                    <tr>
                        <td>Doação recebida de uma pessoa ou comunidade</td>
                        <td>Doação</td>
                        <td>Nome do doador, categoria Doação, valor recebido</td>
                        <td>Sem vínculo, exceto se a doação pagar uma inscrição específica</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="juvenil-lancamento-help__split">
        <div class="juvenil-lancamento-help__panel">
            <span class="juvenil-lancamento-help__step">Comprovantes</span>
            <h3>Quando anexar</h3>
            <p>
                Anexe comprovantes quando houver pagamento, PIX, nota, recibo ou qualquer evidência da movimentação.
                Imagens e PDFs são aceitos, e a observação ajuda a identificar documentos parecidos.
            </p>
            <div class="juvenil-lancamento-help__callout">
                <strong>Regra prática:</strong>
                se outra pessoa precisar auditar o lançamento depois, o comprovante deve explicar a movimentação sem depender de conversa.
            </div>
        </div>

        <figure class="juvenil-lancamento-help__figure">
            <img src="{{ asset('img/docs/lancamento-help-comprovantes.svg') }}" alt="Exemplo visual da área de comprovantes com botão de adicionar documento">
            <figcaption>Use o bloco de comprovantes para anexar PDF, imagem e uma observação curta.</figcaption>
        </figure>
    </section>
</article>
