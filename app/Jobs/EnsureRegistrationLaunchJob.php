<?php

namespace App\Jobs;

use App\Models\Lancamento;
use App\Support\Financeiro\AutomaticRegistrationLaunchService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnsureRegistrationLaunchJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 3600;

    /**
     * @param  class-string<Model>  $registrationType
     */
    public function __construct(
        public string $registrationType,
        public int $registrationId,
        public string $originContext = Lancamento::ORIGIN_CONTEXT_OBSERVER,
        public ?string $batchCode = null,
    ) {}

    public function handle(AutomaticRegistrationLaunchService $service): void
    {
        $service->ensureForRegistration(
            registrationType: $this->registrationType,
            registrationId: $this->registrationId,
            originContext: $this->originContext,
            batchCode: $this->batchCode,
        );
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
                ->shared()
                ->releaseAfter(60)
                ->expireAfter(300),
        ];
    }

    public function uniqueId(): string
    {
        return 'ensure:'.$this->lockKey();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha ao garantir lançamento automático de inscrição.', [
            'registration_type' => $this->registrationType,
            'registration_id' => $this->registrationId,
            'origin_context' => $this->originContext,
            'error' => $exception?->getMessage(),
        ]);
    }

    private function lockKey(): string
    {
        return "automatic-registration-launch:{$this->registrationType}:{$this->registrationId}";
    }
}
