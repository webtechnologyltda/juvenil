<?php

use App\Models\Tribo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses a color picker for the registered tribe color', function () {
    $resource = file_get_contents(app_path('Filament/Resources/TriboResource.php'));

    expect($resource)
        ->toContain('use Filament\\Forms\\Components\\ColorPicker;')
        ->toContain("ColorPicker::make('cor_hex')")
        ->toContain("TextInput::make('cor')")
        ->toContain("'lg' => 4")
        ->toContain("'lg' => 3")
        ->toContain("'lg' => 1")
        ->toContain('TribeColor::resolve')
        ->toContain('->html()');
});

it('stores the custom color on tribes', function () {
    $tribe = Tribo::query()->create([
        'cor' => 'Azul',
        'cor_hex' => '#123abc',
    ]);

    expect($tribe->refresh()->cor_hex)->toBe('#123abc');
});
