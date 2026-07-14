<?php

use App\Support\Reports\PrintableReportImage;
use Illuminate\Support\Facades\Storage;

it('creates and reuses a small cached thumbnail for printable reports', function (): void {
    Storage::fake('public');

    $source = imagecreatetruecolor(2400, 1800);
    $orange = imagecolorallocate($source, 244, 107, 18);
    imagefill($source, 0, 0, $orange);
    ob_start();
    imagejpeg($source, null, 100);
    $original = ob_get_clean();
    unset($source);

    expect($original)->toBeString();

    $originalPath = 'foto-formulario/foto-grande.jpg';
    Storage::disk('public')->put($originalPath, $original);

    $printableImage = app(PrintableReportImage::class);
    $thumbnailPath = $printableImage->thumbnailPath($originalPath);
    $url = $printableImage->url($originalPath);

    Storage::disk('public')->assertExists($thumbnailPath);

    $dimensions = getimagesizefromstring(Storage::disk('public')->get($thumbnailPath));

    expect($url)
        ->toEndWith('/storage/'.$thumbnailPath)
        ->and($dimensions)->toBeArray()
        ->and(max($dimensions[0], $dimensions[1]))->toBeLessThanOrEqual(320)
        ->and(Storage::disk('public')->size($thumbnailPath))->toBeLessThan(strlen($original));

    $lastModified = Storage::disk('public')->lastModified($thumbnailPath);

    expect($printableImage->url($originalPath))->toBe($url)
        ->and(Storage::disk('public')->lastModified($thumbnailPath))->toBe($lastModified);
});

it('keeps external and missing image URLs compatible', function (): void {
    Storage::fake('public');

    $printableImage = app(PrintableReportImage::class);

    expect($printableImage->url('https://example.com/foto.jpg'))
        ->toBe('https://example.com/foto.jpg')
        ->and($printableImage->url('foto-formulario/inexistente.jpg'))
        ->toEndWith('/storage/foto-formulario/inexistente.jpg')
        ->and($printableImage->url(null))->toBeNull();
});

it('resizes new public registration photos before storing them', function (): void {
    expect(file_get_contents(app_path('Livewire/CampistaForm.php')))
        ->toContain("->imageAspectRatio('1:1')")
        ->toContain("->automaticallyResizeImagesMode('cover')")
        ->toContain("->automaticallyResizeImagesToWidth('500')")
        ->toContain("->automaticallyResizeImagesToHeight('500')")
        ->toContain('->automaticallyUpscaleImagesWhenResizing(false)')
        ->toContain('->automaticallyCropImagesToAspectRatio()');
});
