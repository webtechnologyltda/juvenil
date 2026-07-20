<?php

namespace App\Console\Commands\Financeiro;

use App\Jobs\ReconcileRegistrationLaunchesJob;
use App\Support\Financeiro\AutomaticRegistrationLaunchService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('financeiro:reconcile-registration-launches
    {--type= : Tipo de inscrição a processar: campista ou equipe}
    {--dry-run : Mostra a prévia sem criar, reativar ou cancelar lançamentos}
    {--sync : Executa a regularização imediatamente, sem despachar para a fila}')]
#[Description('Regulariza lançamentos automáticos pendentes de campistas e equipe de trabalho.')]
class ReconcileRegistrationLaunchesCommand extends Command
{
    public function handle(AutomaticRegistrationLaunchService $service): int
    {
        $type = $this->normalizedType();

        if (! $this->validType($type)) {
            $this->error('Tipo inválido. Use --type=campista ou --type=equipe.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Prévia da regularização automática de lançamentos.');
            $this->renderSummary($service->preview($type));

            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            $this->info('Executando regularização automática de lançamentos.');
            $this->renderSummary($service->reconcile($type));

            return self::SUCCESS;
        }

        $dispatch = ReconcileRegistrationLaunchesJob::dispatch($type);

        if (config('queue.default') === 'sync') {
            $dispatch->onConnection('database');
        }

        $this->info('Job de regularização automática despachado.');

        return self::SUCCESS;
    }

    private function normalizedType(): ?string
    {
        $type = $this->option('type');

        if ($type === null || $type === '') {
            return null;
        }

        return (string) $type;
    }

    private function validType(?string $type): bool
    {
        return $type === null || array_key_exists($type, AutomaticRegistrationLaunchService::registrationTypes());
    }

    /**
     * @param  array<string, array{candidates: int, created: int, reactivated: int, cancelled: int, skipped: int, failed: int}>  $summary
     */
    private function renderSummary(array $summary): void
    {
        $labels = AutomaticRegistrationLaunchService::typeLabels();

        $this->table(
            ['Tipo', 'Candidatos', 'Criados', 'Reativados', 'Cancelados', 'Pulados', 'Falhas'],
            collect($summary)
                ->map(fn (array $row, string $type): array => [
                    $labels[$type] ?? throw new RuntimeException("Tipo de inscrição inválido: {$type}."),
                    $row['candidates'],
                    $row['created'],
                    $row['reactivated'],
                    $row['cancelled'],
                    $row['skipped'],
                    $row['failed'],
                ])
                ->values()
                ->all(),
        );
    }
}
