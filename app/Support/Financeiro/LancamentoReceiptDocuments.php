<?php

namespace App\Support\Financeiro;

use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Models\Lancamento;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class LancamentoReceiptDocuments
{
    /**
     * @return array<int, array{name: string, path: string, url: string, preview_url: string, type: string, observation: string|null}>
     */
    public function documents(Lancamento $record): array
    {
        return collect(LancamentoForm::normalizeComprovanteState($this->state($record)))
            ->flatMap(function (array $block) use ($record): array {
                $data = is_array($block['data'] ?? null) ? $block['data'] : [];
                $observation = $data['observacao'] ?? null;

                return collect($data['url'] ?? [])
                    ->filter(fn (mixed $file): bool => is_string($file) && filled($file))
                    ->map(fn (string $file): array => $this->document($record, $file, is_string($observation) ? $observation : null))
                    ->all();
            })
            ->values()
            ->all();
    }

    public function containsPath(Lancamento $record, string $path): bool
    {
        return collect($this->documents($record))
            ->contains(fn (array $document): bool => $document['path'] === $path);
    }

    public function diskName(): string
    {
        return (string) config('filament.default_filesystem_disk', config('filesystems.default', 'local'));
    }

    public function mimeType(string $path): string
    {
        return match (strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    private function state(Lancamento $record): mixed
    {
        if (filled($record->comprovante)) {
            return $record->comprovante;
        }

        $raw = $record->getRawOriginal('comprovante');

        if (! is_string($raw) || blank($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }

    /**
     * @return array{name: string, path: string, url: string, preview_url: string, type: string, observation: string|null}
     */
    private function document(Lancamento $record, string $path, ?string $observation): array
    {
        $url = $this->url($record, $path);
        $type = $this->type($path);

        return [
            'name' => $this->name($path),
            'path' => $path,
            'url' => $url,
            'preview_url' => $type === 'pdf' ? $url.'#toolbar=0&navpanes=0' : $url,
            'type' => $type,
            'observation' => filled($observation) ? $observation : null,
        ];
    }

    private function url(Lancamento $record, string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return Str::sanitizeUrl($path) ?? '#';
        }

        return URL::temporarySignedRoute(
            'admin.lancamentos.comprovantes.show',
            now()->addMinutes(config('filament.temporary_file_url_expiry_minutes', 30)),
            [
                'lancamento' => $record,
                'path' => $path,
            ],
        );
    }

    private function name(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: $path;

        return basename($path);
    }

    private function type(string $path): string
    {
        return match (strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg' => 'image',
            'pdf' => 'pdf',
            default => 'file',
        };
    }
}
