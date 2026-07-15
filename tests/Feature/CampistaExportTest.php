<?php

use App\Filament\Exports\CampistaExporter;
use App\Models\Campista;
use Filament\Actions\Exports\Models\Export;

it('exports an unreported payment method without failing', function () {
    $exporter = new CampistaExporter(
        export: new Export,
        columnMap: ['forma_pagamento' => 'Forma de Pagamento'],
        options: [],
    );
    $campista = new Campista(['forma_pagamento' => null]);

    expect($exporter($campista))->toBe(['Não Informado']);
});
