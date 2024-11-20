<?php

namespace App\Filament\Resources\LancamentoResource\Widgets;

use App\Enums\StatusInscricao;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Models\Campista;
use App\Models\Lancamento;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsFinanceiro extends BaseWidget
{
    use HasWidgetShield;
    protected static ?string $pollingInterval = '60s';

    protected static ?string $label = 'Overview Caixa';
    protected function getStats(): array
    {
        $receita = Lancamento::query()
                ->where('status', StatusLacamento::Pago->value)
                ->where('tipo', TipoLacamento::Receita->value)
                ->sum('valor') / 100;
        $receitaFormatted = number_format($receita, 2, ',', '.');

        $despesas = Lancamento::query()
                ->where('status', StatusLacamento::Pago->value)
                ->where('tipo', TipoLacamento::Despesa->value)
                ->sum('valor') / -100;
        $despesasFormatted = number_format($despesas, 2, ',', '.');

        $doacoes = Lancamento::query()
                ->where('status', StatusLacamento::Pago->value)
                ->where('tipo', TipoLacamento::Doacao->value)
                ->sum('valor') / 100;
        $doacoesFormatted = number_format($doacoes, 2, ',', '.');

        $total = $receita + $doacoes - $despesas;
        $totalFormated = number_format($total > 0 ? $total : $total * -1, 2, ',', '.');
        return [
            Stat::make('', 'R$ '. $receitaFormatted)
                ->description('Receita')
                ->descriptionIcon('eva-trending-up')
                ->color(Color::Green),

            Stat::make('', '-R$ '. $despesasFormatted)
                ->description('Despesas')
                ->descriptionIcon('eva-trending-down')
                ->color(Color::Rose),

            Stat::make('', 'R$ '. $doacoesFormatted)
                ->description('Doações')
                ->descriptionIcon('iconoir-donate')
                ->color(Color::Blue),

            Stat::make('', ($total > 0 ? 'R$ ' : '-R$ '). $totalFormated)
                ->description('Total em Caixa')
                ->descriptionIcon('fas-cash-register')
                ->color(Color::Orange),
        ];
    }
}
