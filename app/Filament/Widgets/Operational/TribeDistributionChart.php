<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use App\Support\Tribes\TribeColor;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TribeDistributionChart extends ApexChartWidget
{
    use UsesOperationalDashboardData;

    protected static ?string $chartId = 'tribeDistributionChart';

    protected static ?int $sort = 5;

    protected function getHeading(): ?string
    {
        return 'Distribuição por tribo';
    }

    protected function getSubheading(): ?string
    {
        return 'Inclui campistas ainda sem tribo definida pela equipe.';
    }

    protected function getOptions(): array
    {
        $operationalData = $this->operationalData();
        $data = $this->chartData($operationalData->tribes());
        $labels = array_keys($data);
        $colors = $operationalData->tribeColors();
        $chartColors = array_map(fn (string $tribe): string => $colors[$tribe] ?? TribeColor::resolve(null, $tribe), $labels);

        return [
            'chart' => [
                'type' => 'pie',
                'height' => 320,
                'toolbar' => ['show' => false],
            ],
            'labels' => $labels,
            'series' => array_values($data),
            'legend' => [
                'position' => 'bottom',
                'horizontalAlign' => 'center',
            ],
            'dataLabels' => [
                'enabled' => true,
                'style' => [
                    'colors' => array_map(fn (string $color): string => TribeColor::contrastText($color), $chartColors),
                ],
                'dropShadow' => [
                    'enabled' => false,
                ],
            ],
            'colors' => $chartColors,
            'stroke' => [
                'width' => 2,
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            dataLabels: {
                formatter: function (value, options) {
                    return options.w.config.series[options.seriesIndex]
                }
            },
            tooltip: {
                custom: function ({ series, seriesIndex, w }) {
                    function escapeHtml(value) {
                        return String(value)
                            .replace(/&/g, '&amp;')
                            .replace(/\u003c/g, '&lt;')
                            .replace(/\u003e/g, '&gt;')
                            .replace(new RegExp(String.fromCharCode(34), 'g'), '&quot;')
                            .replace(/'/g, '&#039;')
                    }

                    function readableTextColor(color) {
                        const normalized = String(color || '').trim().toLowerCase()
                        const match = normalized.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/)

                        if (! match) {
                            return '#ffffff'
                        }

                        const hex = match[1].length === 3
                            ? match[1].split('').map(function (part) { return part + part }).join('')
                            : match[1]
                        const channels = [
                            parseInt(hex.slice(0, 2), 16) / 255,
                            parseInt(hex.slice(2, 4), 16) / 255,
                            parseInt(hex.slice(4, 6), 16) / 255,
                        ]
                        const linear = channels.map(function (channel) {
                            return channel > 0.03928
                                ? Math.pow((channel + 0.055) / 1.055, 2.4)
                                : channel / 12.92
                        })
                        const luminance = (0.2126 * linear[0]) + (0.7152 * linear[1]) + (0.0722 * linear[2])

                        return luminance >= 0.45 ? '#03181c' : '#ffffff'
                    }

                    const labels = (w.globals && w.globals.labels) || (w.config && w.config.labels) || []
                    const colors = (w.config && w.config.colors) || []
                    const label = labels[seriesIndex] || ''
                    const value = Number(series[seriesIndex])
                    const formattedValue = Number.isFinite(value) ? value.toFixed(0) : series[seriesIndex]
                    const background = colors[seriesIndex] || '#03181c'
                    const color = readableTextColor(background)
                    const tagStart = '\\x3c'
                    const tagEnd = '\\x3e'

                    return tagStart + 'div style=\\'background:' + background + ';color:' + color + ';border:1px solid rgba(3,24,28,.22);box-shadow:0 10px 24px rgba(3,24,28,.22);font-weight:800;padding:.45rem .65rem;\\'' + tagEnd
                        + tagStart + 'span' + tagEnd + escapeHtml(label) + tagStart + '/span' + tagEnd
                        + tagStart + 'span style=\\'margin-left:.45rem;opacity:.82;\\'' + tagEnd + escapeHtml(formattedValue) + ' campistas' + tagStart + '/span' + tagEnd
                        + tagStart + '/div' + tagEnd
                },
                y: {
                    formatter: function (value) {
                        return Number(value).toFixed(0) + ' campistas'
                    }
                }
            }
        }
        JS);
    }
}
