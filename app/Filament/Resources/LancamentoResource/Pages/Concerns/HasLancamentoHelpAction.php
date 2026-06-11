<?php

namespace App\Filament\Resources\LancamentoResource\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;

trait HasLancamentoHelpAction
{
    protected function lancamentoHelpAction(): Action
    {
        return Action::make('lancamentoHelp')
            ->label('Dúvidas')
            ->icon('heroicon-s-question-mark-circle')
            ->color('gray')
            ->slideOver()
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading('Guia do lançamento financeiro')
            ->modalDescription('Como preencher, vincular inscrições e anexar comprovantes sem perder o controle do valor total.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn () => view('filament.resources.lancamento-resource.partials.help'));
    }
}
