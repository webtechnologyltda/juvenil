<?php

use App\Support\FilamentUploadState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('keeps already stored Filament upload paths without storing them again', function () {
    Storage::fake('public');

    Storage::disk('public')->put('foto-formulario/avatar-processado.jpg', 'conteudo');

    expect(FilamentUploadState::storedPath([
        'upload-key' => 'foto-formulario/avatar-processado.jpg',
    ], 'foto-formulario'))
        ->toBe('foto-formulario/avatar-processado.jpg');
});

it('stores temporary uploaded files when Filament upload state still contains a file object', function () {
    Storage::fake('public');

    $path = FilamentUploadState::storedPath([
        'upload-key' => UploadedFile::fake()->image('avatar.jpg', 500, 500),
    ], 'foto-formulario');

    expect($path)->toStartWith('foto-formulario/')
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
});
