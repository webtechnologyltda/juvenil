<?php

namespace App\Jobs;

use App\Support\Financeiro\AutomaticRegistrationLaunchService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconcileRegistrationLaunchesJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public int $uniqueFor = 3600;

    public function __construct(
        public ?string $type = null,
    ) {}

    public function handle(AutomaticRegistrationLaunchService $service): void
    {
        $service->reconcile($this->type);
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->lockKey()))
                ->releaseAfter(60)
                ->expireAfter(900),
        ];
    }

    public function uniqueId(): string
    {
        return $this->lockKey();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha na regularização automática de lançamentos de inscrição.', [
            'type' => $this->type,
            'error' => $exception?->getMessage(),
        ]);
    }

    private function lockKey(): string
    {
        return 'automatic-registration-launch:reconcile:'.($this->type ?? 'all');
    }
}
