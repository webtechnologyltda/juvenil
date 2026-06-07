# Widgets do Painel Operacional

## Objetivo

Criar um painel inicial para a Operacao do Evento, focado no periodo de pre-evento ate check-in. O painel deve transformar os dados de inscricao dos campistas em filas de acao, alertas e graficos operacionais, sem misturar controle financeiro.

## Publico Principal

O usuario principal e a equipe de Operacao do Evento. O painel deve responder rapidamente:

- quantas inscricoes ainda exigem acao;
- quem esta pronto para check-in;
- quais pendencias precisam ser resolvidas antes do evento;
- quais cuidados sensiveis exigem equipe autorizada;
- como estao tribos/grupos, camisetas, comunidades e fluxo de chegada.

## Escopo da Primeira Entrega

Incluido:

- dashboard operacional inicial;
- filtros globais da pagina de dashboard;
- widgets nativos do Filament para cards, tabelas e filas de acao;
- Apex Charts para graficos;
- protecao de dados sensiveis de saude/cuidados;
- papeis `Administrador` e `Enfermaria`;
- testes focados em regras, seguranca e tolerancia a dados incompletos.

Fora do escopo:

- painel financeiro;
- pagina dedicada de Enfermaria;
- pagina customizada de relatorios secundarios;
- mudancas no formulario publico alem das necessarias para seguranca de exibicao.

## Regras de Dados

### Inscricoes validas

Por padrao, widgets operacionais consideram como validas todas as inscricoes que nao estejam canceladas.

`validas = status != Cancelado`

Inscricoes pendentes continuam sendo demanda operacional real, pois exigem acompanhamento antes do evento.

### Esteira operacional

A primeira faixa do dashboard deve exibir:

1. Pendentes de pagamento: `status = Pendente`
2. Pagas: `status = Pago`
3. Aguardando check-in: `status = Pago` e `presenca = false`
4. Presentes: `status = Pago` e `presenca = true`
5. Canceladas: indicador lateral/atalho, fora da leitura principal de validas

Cada bloco principal deve ter caminho para a lista de inscricoes filtrada.

### Financeiro

O dashboard operacional nao exibe valores financeiros. Ele pode mostrar pendentes de pagamento como fila de acao, mas receita, caixa, despesas e valores estimados pertencem ao futuro painel financeiro.

### Tribos

Tribo e uma organizacao interna, definida por quem usa o sistema. Nao e pendencia do campista.

O painel deve mostrar:

- com tribo definida;
- sem tribo definida;
- distribuicao por tribo, incluindo "Sem tribo".

Por padrao, a distribuicao usa inscricoes validas e respeita filtros globais.

### Camisetas

O painel deve mostrar:

- distribuicao por tamanho;
- destaque/lista curta para tamanho `Outros`, usando `form_data.tamanho_camiseta_outro`.

### Localizacao e comunidade

Localizacao e apoio operacional secundario. O painel deve priorizar:

- distribuicao por paroquia/comunidade;
- cidade/bairro apenas como relatorio secundario ou grafico futuro.

### Pendencias de cadastro e contato

O painel deve listar pendencias que atrapalham a operacao:

- telefone do campista ausente;
- primeiro telefone do responsavel ausente;
- nome do primeiro responsavel ausente;
- comunidade/paroquia ausente;
- tamanho de camiseta ausente;
- foto ausente.

Tribo nao entra nessa lista porque e classificacao interna.

### Dados incompletos

Todos os widgets devem ser tolerantes a `form_data` incompleto:

- data de nascimento ausente ou invalida entra como "Sem data";
- comunidade ausente entra como "Sem comunidade";
- camiseta ausente entra como "Sem tamanho";
- tribo ausente entra como "Sem tribo";
- chaves de saude ausentes nao quebram consultas nem vazam detalhes.

## Saude e Dados Sensiveis

Campos sensiveis:

- `form_data.toma_remedio`
- `form_data.remedio`
- `form_data.tem_recomendacao`
- `form_data.recomendacao`
- campos correlatos usados pela Enfermaria, como altura e peso quando exibidos nesse contexto.

### Modelo de acesso

O dashboard deve ter dois niveis:

1. Resumo operacional seguro para quem ve o dashboard:
   - contagens de campistas que tomam remedio;
   - contagens de campistas com recomendacao especial;
   - contagens de campistas com ambos;
   - sem nomes e sem texto de remedio/recomendacao.

2. Detalhe sensivel para usuarios autorizados:
   - tabela curta com nome, idade, tribo e indicadores;
   - texto completo de remedio/recomendacao somente na ficha ou exportacao autorizada;
   - permissao especifica `view_sensitive_health_campista`.

### Protecao obrigatoria

A permissao sensivel deve proteger:

- dashboard;
- ficha de inscricao no painel;
- colunas de tabela;
- exportacao;
- widgets/listas medicas.

Nao basta esconder o dado no dashboard se ele ainda aparece em outro ponto do painel.

## Papeis

### Super Administrador

Acesso total, incluindo dados sensiveis.

### Administrador

Operacao geral do evento. Pode ver o dashboard operacional, gerenciar inscricoes, tribos, check-in e pendencias. Nao ve detalhes sensiveis de saude por padrao.

### Enfermaria

Acesso focado em saude/cuidados. Pode consultar campistas e editar apenas campos de saude/cuidados quando essa edicao for implementada. Nao herda financeiro nem administracao global.

Na primeira entrega nao sera criada uma pagina dedicada de Enfermaria.

## Filtros Globais

A pagina `App\Filament\Dashboard` deve expor filtros globais para:

- status;
- tribo;
- paroquia/comunidade;
- presenca.

Os filtros devem afetar todos os widgets onde fizer sentido: esteira, check-in, saude, camisetas, tribos e graficos.

O estado inicial deve mostrar inscricoes validas por padrao, excluindo canceladas da maioria dos widgets.

## Widgets

### Nativos do Filament

Usar widgets nativos para:

- esteira operacional;
- cards de acao;
- tabelas de pendencias;
- tabela curta de saude para autorizados.

### Apex Charts

Usar `leandrocfe/filament-apex-charts` como padrao para graficos visuais do dashboard. O plugin tambem podera ser usado no futuro painel financeiro.

Graficos da primeira entrega:

1. Funil operacional: pendentes, pagas, aguardando check-in, presentes.
2. Inscricoes por dia, usando `created_at`.
3. Camisetas por tamanho, com destaque para "Outros".
4. Distribuicao por tribo, incluindo "Sem tribo".
5. Comunidade/paroquia.
6. Idade e sexo em formato compacto.

Cidade/bairro fica fora da primeira tela ou como relatorio secundario futuro.

## Organizacao de Codigo

Criar um namespace operacional:

- `app/Filament/Widgets/Operational/`

Criar servico compartilhado:

- `app/Support/Dashboard/OperationalDashboardData.php`

Responsabilidades do servico:

- aplicar filtros globais;
- construir escopo de inscricoes validas;
- calcular esteira;
- calcular distribuicoes;
- identificar pendencias;
- aplicar regras de dados incompletos;
- oferecer consultas para widgets.

Widgets previstos:

- `OperationalPipelineStats`
- `OperationalFunnelChart`
- `RegistrationTrendChart`
- `ShirtSizeChart`
- `TribeDistributionChart`
- `CommunityDistributionChart`
- `DemographicsChart`
- `SensitiveHealthSummary`
- `SensitiveHealthTable`
- `OperationalPendingTasksTable`

## Testes

A primeira entrega deve cobrir:

- calculo de inscricoes validas;
- esteira operacional;
- aguardando check-in;
- distribuicoes por tribo, camiseta, comunidade, idade e sexo;
- filtros globais aplicados aos widgets;
- dados incompletos sem excecoes;
- usuario sem permissao sensivel nao ve detalhes de saude;
- exportacao/ficha nao expoe campos sensiveis sem permissao;
- plugin Apex registrado no painel.

## Referencias

- Plugin Apex Charts para Filament: https://filamentphp.com/plugins/leandrocfe-apex-charts
- Documentacao ApexCharts: https://apexcharts.com/docs/options/
