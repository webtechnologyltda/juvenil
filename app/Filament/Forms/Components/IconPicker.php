<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class IconPicker extends Field
{
    protected string $view = 'filament.forms.components.icon-picker';

    protected string|Closure $catalog = 'all';

    /**
     * @var list<string>|Closure
     */
    protected array|Closure $customIcons = [];

    protected string|Closure|null $placeholder = null;

    /**
     * Define qual catálogo o modal usa: `all` (todos os ícones) ou
     * `payment-methods` (curado).
     */
    public function catalog(string|Closure $catalog): static
    {
        $this->catalog = $catalog;

        return $this;
    }

    public function getCatalog(): string
    {
        return $this->evaluate($this->catalog);
    }

    /**
     * Lista de nomes de ícones a serem usados em vez do catálogo padrão.
     * Útil pra preview/seleção restrita.
     *
     * @param  list<string>|Closure  $names
     */
    public function customIcons(array|Closure $names): static
    {
        $this->customIcons = $names;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getCustomIcons(): array
    {
        $resolved = $this->evaluate($this->customIcons);

        return is_array($resolved) ? array_values($resolved) : [];
    }

    public function placeholder(string|Closure|null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(): ?string
    {
        return $this->evaluate($this->placeholder);
    }
}
