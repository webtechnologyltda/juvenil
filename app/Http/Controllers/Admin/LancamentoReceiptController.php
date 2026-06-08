<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\LancamentoResource;
use App\Http\Controllers\Controller;
use App\Models\Lancamento;
use App\Support\Financeiro\LancamentoReceiptDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class LancamentoReceiptController extends Controller
{
    public function __invoke(Request $request, Lancamento $lancamento, LancamentoReceiptDocuments $documents): Response
    {
        abort_unless(LancamentoResource::canView($lancamento), 403);

        $path = $request->query('path');

        abort_unless(is_string($path) && filled($path), 404);
        abort_unless($documents->containsPath($lancamento, $path), 404);

        $disk = Storage::disk($documents->diskName());

        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $documents->mimeType($path),
        ]);
    }
}
