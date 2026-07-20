<?php

namespace App\Filament\Resources\LancamentoResource\Widgets;

use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Widgets\Concerns\SupportsWidgetShield;
use App\Models\Lancamento;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsFinanceiro extends BaseWidget
{
    use SupportsWidgetShield;

    protected ?string $pollingInterval = '60s';

    protected static ?string $label = 'Overview Caixa';

    protected function getStats(): array
    {
        $totals = Lancamento::query()
            ->where('status', StatusLacamento::Pago->value)
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN tipo = ? THEN valor ELSE 0 END), 0) AS receita,
                COALESCE(SUM(CASE WHEN tipo = ? THEN valor ELSE 0 END), 0) AS despesas,
                COALESCE(SUM(CASE WHEN tipo = ? THEN valor ELSE 0 END), 0) AS doacoes',
                [
                    TipoLacamento::Receita->value,
                    TipoLacamento::Despesa->value,
                    TipoLacamento::Doacao->value,
                ],
            )
            ->firstOrFail();

        $receita = (int) $totals->receita / 100;
        $receitaFormatted = number_format($receita, 2, ',', '.');

        $despesas = (int) $totals->despesas / -100;
        $despesasFormatted = number_format($despesas, 2, ',', '.');

        $doacoes = (int) $totals->doacoes / 100;
        $doacoesFormatted = number_format($doacoes, 2, ',', '.');

        $total = $receita + $doacoes - $despesas;
        $totalFormated = number_format($total > 0 ? $total : $total * -1, 2, ',', '.');

        return [
            Stat::make('', 'R$ '.$receitaFormatted)
                ->description('Receita')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color(Color::Green),

            Stat::make('', '-R$ '.$despesasFormatted)
                ->description('Despesas')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color(Color::Rose),

            Stat::make('', 'R$ '.$doacoesFormatted)
                ->description('Doações')
                ->descriptionIcon('iconoir-donate')
                ->color(Color::Blue),

            Stat::make('', ($total > 0 ? 'R$ ' : '-R$ ').$totalFormated)
                ->description('Total em Caixa')
                ->descriptionIcon('fas-cash-register')
                ->color(Color::Orange),
        ];
    }
}
