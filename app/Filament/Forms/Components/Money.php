<?php

namespace App\Filament\Forms\Components;

use Leandrocfe\FilamentPtbrFormFields\Money as BaseMoney;

class Money extends BaseMoney
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extraAlpineAttributes(fn (self $component): array => [
            ...$component->getOnKeyPress(),
            ...$component->getOnKeyUp(),
            ...$component->getOnBlur(),
        ]);
    }
}
