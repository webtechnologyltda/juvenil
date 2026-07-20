<?php

namespace App\Filament\Widgets\Financial\Concerns;

use App\Support\Dashboard\FinancialDashboardData;
use App\Support\Dashboard\FinancialDashboardDataSet;
use Filament\Support\RawJs;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

trait UsesFinancialDashboardData
{
    use InteractsWithPageFilters;

    protected function financialData(): FinancialDashboardDataSet
    {
        return app(FinancialDashboardData::class)->forFilters($this->pageFilters ?? []);
    }

    protected function financialQuery(): Builder
    {
        return app(FinancialDashboardData::class)->queryForFilters($this->pageFilters ?? []);
    }

    protected function emptyChartData(array $data): array
    {
        return $data === [] ? ['Sem dados' => 0] : $data;
    }

    protected function currencyYAxisJsOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        }).format(Number(value || 0) / 100)
                    }
                }
            },
            dataLabels: {
                formatter: function (value) {
                    return new Intl.NumberFormat('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    }).format(Number(value || 0) / 100)
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        }).format(Number(value || 0) / 100)
                    }
                }
            }
        }
        JS);
    }

    protected function currencyXAxisJsOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            xaxis: {
                labels: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        }).format(Number(value || 0) / 100)
                    }
                }
            },
            dataLabels: {
                formatter: function (value) {
                    return new Intl.NumberFormat('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    }).format(Number(value || 0) / 100)
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        }).format(Number(value || 0) / 100)
                    }
                }
            }
        }
        JS);
    }

    protected function currencyPieJsOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            dataLabels: {
                formatter: function (value, options) {
                    return new Intl.NumberFormat('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    }).format(Number(options.w.config.series[options.seriesIndex] || 0) / 100)
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        }).format(Number(value || 0) / 100)
                    }
                }
            }
        }
        JS);
    }

    protected function money(int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';

        return $sign.'R$ '.number_format(abs($amount) / 100, 2, ',', '.');
    }
}
