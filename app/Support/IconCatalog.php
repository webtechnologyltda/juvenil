<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Catálogo de ícones para o IconPicker. Inclui Heroicons (`heroicon-o-`),
 * FontAwesome solid (`fas-`), Google Material Design (`gmdi-`) e
 * IconPark (`iconpark-`). Variantes (`-o`, `-r`, `-s`, `-tt`) são filtradas
 * para ficar só a versão "filled" principal de cada ícone.
 *
 * Cada item tem `icon` (id completo do blade-icons) e `aliases` para busca.
 */
class IconCatalog
{
    /**
     * Lista curada para finanças/pagamento.
     *
     * @return list<array{icon: string, aliases: list<string>}>
     */
    public static function finance(): array
    {
        return [
            ['icon' => 'heroicon-o-banknotes', 'aliases' => ['dinheiro', 'cedula', 'cédula', 'cash', 'real', 'nota', 'pagamento']],
            ['icon' => 'fas-money-bill', 'aliases' => ['nota', 'dinheiro', 'cedula', 'real']],
            ['icon' => 'fas-money-bill-wave', 'aliases' => ['nota', 'dinheiro']],
            ['icon' => 'fas-money-bills', 'aliases' => ['dinheiro', 'notas']],
            ['icon' => 'fas-sack-dollar', 'aliases' => ['caixa', 'tesouro', 'saco', 'fundo']],
            ['icon' => 'fas-piggy-bank', 'aliases' => ['cofrinho', 'poupança', 'poupanca', 'economia']],
            ['icon' => 'fas-coins', 'aliases' => ['moedas', 'troco', 'centavos']],
            ['icon' => 'heroicon-o-credit-card', 'aliases' => ['cartao', 'cartão', 'crédito', 'credito', 'débito', 'debito']],
            ['icon' => 'fas-credit-card', 'aliases' => ['cartao', 'cartão']],
            ['icon' => 'fab-pix', 'aliases' => ['pix', 'qrcode', 'transferência']],
            ['icon' => 'heroicon-o-qr-code', 'aliases' => ['qrcode', 'qr', 'código']],
            ['icon' => 'fab-google-pay', 'aliases' => ['google pay', 'gpay']],
            ['icon' => 'fab-apple-pay', 'aliases' => ['apple pay']],
            ['icon' => 'fab-paypal', 'aliases' => ['paypal']],
            ['icon' => 'heroicon-o-building-library', 'aliases' => ['banco', 'agência', 'agencia', 'instituição']],
            ['icon' => 'fas-building-columns', 'aliases' => ['banco', 'agência', 'instituição']],
            ['icon' => 'heroicon-o-wallet', 'aliases' => ['carteira', 'wallet', 'bolso']],
            ['icon' => 'fas-wallet', 'aliases' => ['carteira']],
            ['icon' => 'heroicon-o-document-text', 'aliases' => ['nota', 'recibo', 'documento', 'comprovante', 'fatura', 'boleto']],
            ['icon' => 'fas-file-invoice', 'aliases' => ['fatura', 'boleto', 'nf', 'nota fiscal']],
            ['icon' => 'fas-file-invoice-dollar', 'aliases' => ['fatura', 'cobrança', 'cobranca']],
            ['icon' => 'fas-receipt', 'aliases' => ['recibo', 'comprovante', 'cupom']],
            ['icon' => 'fas-barcode', 'aliases' => ['boleto', 'código de barras', 'codigo de barras', 'barras']],
            ['icon' => 'heroicon-o-arrows-right-left', 'aliases' => ['transferência', 'transferencia', 'transfer', 'troca']],
            ['icon' => 'fas-money-bill-transfer', 'aliases' => ['transferência', 'transfer']],
        ];
    }

    /**
     * Catálogo completo agregado das bibliotecas instaladas. Cacheado pra evitar
     * escanear o disco em cada request.
     *
     * @return list<array{icon: string, aliases: list<string>}>
     */
    public static function all(): array
    {
        return Cache::rememberForever('icon-catalog:all', static function (): array {
            $sources = [
                ['prefix' => 'heroicon-o-', 'path' => base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg'), 'filter' => fn (string $f): bool => str_starts_with($f, 'o-')],
                ['prefix' => 'fas-', 'path' => base_path('vendor/owenvoke/blade-fontawesome/resources/svg/solid')],
                ['prefix' => 'gmdi-', 'path' => base_path('vendor/codeat3/blade-google-material-design-icons/resources/svg'), 'filter' => static fn (string $f): bool => ! preg_match('/-(o|r|s|tt)\.svg$/', $f)],
                ['prefix' => 'iconpark-', 'path' => base_path('vendor/codeat3/blade-iconpark/resources/svg'), 'filter' => static fn (string $f): bool => ! preg_match('/-o\.svg$/', $f)],
                ['prefix' => 'mdi-', 'path' => base_path('vendor/postare/blade-mdi/resources/svg')],
            ];

            $catalog = [];

            foreach ($sources as $source) {
                if (! is_dir($source['path'])) {
                    continue;
                }

                $files = scandir($source['path']);

                if ($files === false) {
                    continue;
                }

                foreach ($files as $file) {
                    if (! str_ends_with($file, '.svg')) {
                        continue;
                    }

                    if (isset($source['filter']) && ! ($source['filter'])($file)) {
                        continue;
                    }

                    $baseName = substr($file, 0, -4);

                    if ($source['prefix'] === 'heroicon-o-') {
                        $baseName = substr($baseName, 2);
                    }

                    $iconName = $source['prefix'].$baseName;

                    $catalog[] = [
                        'icon' => $iconName,
                        'aliases' => self::aliasesFor($baseName),
                    ];
                }
            }

            usort($catalog, static fn (array $a, array $b): int => strcmp($a['icon'], $b['icon']));

            return $catalog;
        });
    }

    /**
     * Default selecionável: ícones para categorias e grupos (catálogo completo).
     *
     * @return list<array{icon: string, aliases: list<string>}>
     */
    public static function categories(): array
    {
        return self::all();
    }

    /**
     * Default selecionável: ícones para métodos de pagamento (curado).
     *
     * @return list<array{icon: string, aliases: list<string>}>
     */
    public static function paymentMethods(): array
    {
        $extras = [
            ['icon' => 'fab-cc-visa', 'aliases' => ['visa', 'cartao', 'cartão']],
            ['icon' => 'fab-cc-mastercard', 'aliases' => ['mastercard', 'master', 'cartao']],
            ['icon' => 'fab-cc-amex', 'aliases' => ['amex', 'american express', 'cartao']],
            ['icon' => 'fab-cc-discover', 'aliases' => ['discover', 'cartao']],
            ['icon' => 'fab-cc-diners-club', 'aliases' => ['diners', 'cartao']],
            ['icon' => 'fab-cc-jcb', 'aliases' => ['jcb', 'cartao']],
            ['icon' => 'fab-cc-paypal', 'aliases' => ['paypal']],
            ['icon' => 'fab-cc-apple-pay', 'aliases' => ['apple pay']],
            ['icon' => 'fab-cc-amazon-pay', 'aliases' => ['amazon pay']],
            ['icon' => 'fab-cc-stripe', 'aliases' => ['stripe']],
            ['icon' => 'fab-bitcoin', 'aliases' => ['bitcoin', 'btc', 'crypto', 'cripto']],
            ['icon' => 'fas-piggy-bank', 'aliases' => ['poupança', 'poupanca', 'economia', 'cofrinho']],
            ['icon' => 'fas-sack-dollar', 'aliases' => ['saco', 'caixa', 'fundo']],
            ['icon' => 'fas-hand-holding-dollar', 'aliases' => ['doação', 'doacao', 'oferta', 'dízimo', 'dizimo']],
            ['icon' => 'fas-file-invoice-dollar', 'aliases' => ['boleto', 'fatura', 'cobrança']],
            ['icon' => 'fas-barcode', 'aliases' => ['boleto', 'fatura', 'cobrança']],
        ];

        return array_merge(self::finance(), $extras);
    }

    /**
     * Quebra o nome em tokens (separa por hífen) e adiciona o nome cru pra
     * `name.includes()` casar com a query do usuário.
     *
     * @return list<string>
     */
    private static function aliasesFor(string $baseName): array
    {
        $clean = Str::of($baseName)->replace('-', ' ')->lower()->__toString();

        return array_values(array_unique(array_filter([
            $clean,
            ...explode('-', $baseName),
        ])));
    }

    /**
     * Traduz uma query em pt_BR para uma lista de termos em inglês (sempre inclui
     * o termo original também). Permite o usuário buscar "carro" e encontrar
     * ícones que contenham "car" no nome ou nos aliases.
     *
     * @return list<string>
     */
    public static function translateQuery(string $query): array
    {
        $query = trim(mb_strtolower($query));

        if ($query === '') {
            return [];
        }

        $terms = [$query];
        $dictionary = self::translationDictionary();

        // Tokeniza a query em palavras (espaços, hífens, pontos) e busca cada
        // token no dicionário por match exato. Evita falsos positivos como
        // "doacao" casar com a chave "cao" via substring.
        $tokens = array_filter(preg_split('/[\s\-\.]+/u', $query) ?: []);

        foreach ($tokens as $token) {
            $terms[] = $token;

            if (isset($dictionary[$token])) {
                foreach ((array) $dictionary[$token] as $term) {
                    $terms[] = $term;
                }
            }
        }

        // Frase inteira — chaves compostas (`guarda-sol`, `nota fiscal`) só
        // casam assim.
        if (isset($dictionary[$query])) {
            foreach ((array) $dictionary[$query] as $term) {
                $terms[] = $term;
            }
        }

        return array_values(array_unique(array_filter($terms, static fn (string $t): bool => $t !== '')));
    }

    /**
     * Dicionário pt_BR → en para tradução de buscas no IconPicker. Carregado
     * de `lang/pt_BR/icon_search.php` — fica fora do código pra facilitar
     * manutenção e permitir contribuições sem mexer em classes.
     *
     * @return array<string, string|list<string>>
     */
    private static function translationDictionary(): array
    {
        /** @var array<string, string|list<string>> $dictionary */
        $dictionary = trans('icon_search', locale: 'pt_BR');

        return is_array($dictionary) ? $dictionary : [];
    }
}
