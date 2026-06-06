<?php

it('loads the Filament component styles required by the public registration form', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('@layer theme, base, legacy, components, utilities;')
        ->toContain("@import './output.css' layer(legacy);")
        ->toContain("../../vendor/filament/support/resources/css/index.css")
        ->toContain("../../vendor/filament/actions/resources/css/index.css")
        ->toContain("../../vendor/filament/forms/resources/css/index.css")
        ->toContain("../../vendor/filament/notifications/resources/css/index.css")
        ->toContain("../../vendor/filament/schemas/resources/css/index.css");
});
