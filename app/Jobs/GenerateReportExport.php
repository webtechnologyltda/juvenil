<?php

namespace App\Jobs;

use App\Actions\Reports\ExportCampistaReportHtmlAction;
use App\Enums\ReportExportStatus;
use App\Models\ReportExport;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

#[FailOnTimeout]
#[Timeout(900)]
#[Tries(1)]
class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $reportExportId) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('report-export-'.$this->reportExportId))->expireAfter(900),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'report-export:'.$this->reportExportId,
        ];
    }

    public function handle(ExportCampistaReportHtmlAction $reportHtml): void
    {
        $export = ReportExport::query()
            ->with('user')
            ->findOrFail($this->reportExportId);

        Log::info('Report export job started.', [
            'report_export_id' => $export->id,
            'report_type' => $export->report_type->value,
        ]);

        if ($export->isReady() && $export->file_path !== null && Storage::disk($export->disk)->exists($export->file_path)) {
            Log::info('Report export job skipped because file already exists.', [
                'report_export_id' => $export->id,
                'file_path' => $export->file_path,
            ]);

            return;
        }

        $export->forceFill([
            'status' => ReportExportStatus::Processing,
            'error_message' => null,
            'progress_current' => 10,
            'progress_total' => 100,
            'progress_message' => 'Carregando dados do relatório.',
            'started_at' => now(),
            'finished_at' => null,
        ])->save();

        try {
            if ($export->user === null) {
                throw new RuntimeException('Usuário do relatório não está mais disponível.');
            }

            $this->updateProgress($export, 'Renderizando arquivo imprimível.', 70, 100);

            $contents = $reportHtml->output(
                type: $export->report_type,
                filters: $export->filters,
                user: $export->user,
            );

            $this->updateProgress($export, 'Salvando arquivo gerado.', 95, 100);

            Storage::disk($export->disk)->put((string) $export->file_path, $contents);

            $export->forceFill([
                'status' => ReportExportStatus::Ready,
                'error_message' => null,
                'progress_current' => 100,
                'progress_total' => 100,
                'progress_message' => 'Relatório pronto.',
                'finished_at' => now(),
                'expires_at' => now()->addDays(2),
            ])->save();

            Log::info('Report export job completed.', [
                'report_export_id' => $export->id,
                'file_path' => $export->file_path,
            ]);

            $this->sendReadyNotification($export);
        } catch (Throwable $exception) {
            $this->markAsFailed($export, $exception);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $export = ReportExport::query()->find($this->reportExportId);

        if ($export !== null && $exception !== null) {
            if (! $export->isFailed()) {
                $this->markAsFailed($export, $exception);
            }

            $this->sendFailedNotification($export, $exception);
        }
    }

    private function updateProgress(ReportExport $export, string $message, int $current, int $total): void
    {
        $export->forceFill([
            'progress_current' => max(0, $current),
            'progress_total' => max(1, $total),
            'progress_message' => $message,
        ])->save();
    }

    private function markAsFailed(ReportExport $export, Throwable $exception): void
    {
        $export->forceFill([
            'status' => ReportExportStatus::Failed,
            'error_message' => str($exception->getMessage())->limit(1000)->toString(),
            'progress_message' => 'Falhou: '.str($exception->getMessage())->limit(240)->toString(),
            'finished_at' => now(),
        ])->save();

        Log::error('Report export job failed.', [
            'report_export_id' => $export->id,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }

    private function sendReadyNotification(ReportExport $export): void
    {
        $export->loadMissing('user');

        if ($export->user === null) {
            return;
        }

        FilamentNotification::make()
            ->title('Relatório pronto para abrir')
            ->body($export->filename)
            ->success()
            ->actions([
                NotificationAction::make('open')
                    ->label('Abrir relatório')
                    ->button()
                    ->url(route('admin.reports.exports.file', [
                        'reportExport' => $export,
                        'inline' => '1',
                    ]), shouldOpenInNewTab: true)
                    ->markAsRead(),
            ])
            ->sendToDatabase($export->user, isEventDispatched: true);
    }

    private function sendFailedNotification(ReportExport $export, Throwable $exception): void
    {
        $export->loadMissing('user');

        if ($export->user === null) {
            return;
        }

        FilamentNotification::make()
            ->title('Falha ao gerar relatório')
            ->body(str($exception->getMessage())->limit(240)->toString())
            ->danger()
            ->sendToDatabase($export->user, isEventDispatched: true);
    }
}
