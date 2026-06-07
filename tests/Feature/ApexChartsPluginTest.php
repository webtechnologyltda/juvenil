<?php

use Filament\Facades\Filament;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

it('registers the Apex Charts plugin on the admin panel', function () {
    $panel = Filament::getPanel('admin');

    expect($panel->getPlugin('filament-apex-charts'))
        ->toBeInstanceOf(FilamentApexChartsPlugin::class);
});
