<?php

namespace App\Actions\Reports;

use App\Enums\ReportExportStatus;
use App\Jobs\GenerateReportExport;
use App\Models\ReportExport;
use App\Models\User;
use App\Support\Reports\CampistaReportType;
use Illuminate\Support\Facades\Storage;
use JsonException;

class EnsureReportExportAction
{
    public function __construct(private readonly ExportCampistaReportHtmlAction $reportHtml) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(User $user, CampistaReportType $type, array $filters): ReportExport
    {
        $normalizedFilters = $this->normalizedFilters($filters, $type);
        $filtersHash = $this->filtersHash($normalizedFilters);

        $export = ReportExport::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'report_type' => $type->value,
                'filters_hash' => $filtersHash,
            ],
            [
                'status' => ReportExportStatus::Pending,
                'filters' => $normalizedFilters,
                'disk' => config('filesystems.default'),
                'filename' => $this->reportHtml->filenameFor($type, $normalizedFilters),
                'progress_current' => 0,
                'progress_total' => 100,
                'progress_message' => 'Na fila para geração.',
                'expires_at' => now()->addDays(2),
            ],
        );

        $shouldDispatch = $export->wasRecentlyCreated;

        if ($export->file_path === null) {
            $export->file_path = $this->filePathFor($export, $type);
            $export->save();
        }

        if ($this->needsRegeneration($export)) {
            $export->forceFill([
                'status' => ReportExportStatus::Pending,
                'error_message' => null,
                'progress_current' => 0,
                'progress_total' => 100,
                'progress_message' => 'Na fila para geração.',
                'started_at' => null,
                'finished_at' => null,
                'expires_at' => now()->addDays(2),
            ])->save();

            $shouldDispatch = true;
        }

        if ($shouldDispatch) {
            $pendingDispatch = GenerateReportExport::dispatch($export->id)
                ->onQueue((string) config('reports.exports.queue', 'default'));

            if (filled($connection = config('reports.exports.queue_connection'))) {
                $pendingDispatch->onConnection((string) $connection);
            }
        }

        return $export->refresh();
    }

    private function needsRegeneration(ReportExport $export): bool
    {
        if ($export->isFailed()) {
            return false;
        }

        if (
            $export->status === ReportExportStatus::Processing
            && $export->updated_at->lt(now()->subMinutes((int) config('reports.exports.stale_processing_minutes', 20)))
        ) {
            $export->forceFill([
                'status' => ReportExportStatus::Failed,
                'error_message' => 'A geração ficou sem atualização por muito tempo. Tente gerar novamente ou reduza os filtros.',
                'progress_message' => 'Falhou: geração sem atualização recente.',
                'finished_at' => now(),
            ])->save();

            return false;
        }

        if ($export->status === ReportExportStatus::Pending && $export->updated_at->lt(now()->subMinutes(15))) {
            return true;
        }

        if (! $export->isReady()) {
            return false;
        }

        if ($export->expires_at !== null && $export->expires_at->isPast()) {
            return true;
        }

        return $export->file_path === null
            || ! Storage::disk($export->disk)->exists($export->file_path);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizedFilters(array $filters, CampistaReportType $type): array
    {
        $query = $filters;
        $query['_template_version'] = $this->templateVersionFor($type);

        foreach (['status', 'tribo_id'] as $key) {
            if (isset($query[$key]) && is_array($query[$key])) {
                sort($query[$key]);
            }
        }

        ksort($query);

        return $query;
    }

    private function templateVersionFor(CampistaReportType $type): string
    {
        $versions = config('reports.exports.template_versions', []);

        return (string) ($versions[$type->value] ?? '1');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filtersHash(array $filters): string
    {
        try {
            return hash('sha256', json_encode($filters, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            return hash('sha256', serialize($filters));
        }
    }

    private function filePathFor(ReportExport $export, CampistaReportType $type): string
    {
        return 'report-exports/user-'.$export->user_id.'/'.$export->id.'-'.$type->value.'.html';
    }
}
