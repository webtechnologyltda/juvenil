<?php

namespace App\Livewire;

use App\Support\IconCatalog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Lazy]
class IconPickerModal extends Component
{
    public const int PAGE_SIZE = 240;

    #[Locked]
    public string $statePath = '';

    public string $search = '';

    public string $catalog = 'all';

    /**
     * @var array<int, string>
     */
    #[Locked]
    public array $customIcons = [];

    public int $limit = self::PAGE_SIZE;

    /**
     * @param  array<int, string>  $customIcons
     */
    public function mount(string $statePath, string $catalog = 'all', array $customIcons = []): void
    {
        $this->statePath = $statePath;
        $this->catalog = $catalog;
        $this->customIcons = $customIcons;
    }

    public function updatedSearch(): void
    {
        $this->limit = self::PAGE_SIZE;
    }

    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    public function select(string $icon): void
    {
        $this->dispatch('icon-picker-selected-'.self::eventToken($this->statePath), icon: $icon);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.placeholders.icon-picker-modal');
    }

    public static function eventToken(string $statePath): string
    {
        return md5($statePath);
    }

    /**
     * Match por fronteira de palavra: token tem que começar exatamente com o termo,
     * usando hífen/espaço como separador. Evita falsos positivos como "owl" casando
     * com "bowl" / "bowling". Termos curtos (<2) só casam por igualdade exata pra
     * não estourar a lista.
     *
     * @param  array{icon: string, aliases: list<string>}  $item
     * @param  list<string>  $terms
     */
    private static function matchesAnyTerm(array $item, array $terms): bool
    {
        $name = mb_strtolower($item['icon']);
        $nameTokens = preg_split('/[\s\-]+/u', $name) ?: [];

        $aliasTokens = [];
        foreach ($item['aliases'] as $alias) {
            foreach (preg_split('/[\s\-]+/u', mb_strtolower($alias)) ?: [] as $token) {
                if ($token !== '') {
                    $aliasTokens[] = $token;
                }
            }
        }

        $allTokens = array_unique(array_merge($nameTokens, $aliasTokens));

        foreach ($terms as $term) {
            $term = mb_strtolower($term);
            $needsExact = mb_strlen($term) < 3;

            foreach ($allTokens as $token) {
                if ($needsExact ? $token === $term : str_starts_with($token, $term)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function render(): View
    {
        $catalog = match (true) {
            $this->customIcons !== [] => array_map(
                static fn (string $name): array => ['icon' => $name, 'aliases' => [$name]],
                $this->customIcons,
            ),
            $this->catalog === 'payment-methods' => IconCatalog::paymentMethods(),
            default => IconCatalog::all(),
        };

        $terms = IconCatalog::translateQuery($this->search);

        $filtered = $terms === []
            ? $catalog
            : array_values(array_filter(
                $catalog,
                static fn (array $item): bool => self::matchesAnyTerm($item, $terms),
            ));

        $total = count($filtered);
        $visible = array_slice($filtered, 0, $this->limit);

        return view('livewire.icon-picker-modal', [
            'icons' => $visible,
            'total' => $total,
            'hasMore' => $total > count($visible),
        ]);
    }
}
