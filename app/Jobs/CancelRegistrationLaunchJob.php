<?php

namespace App\Jobs;

use App\Support\Financeiro\AutomaticRegistrationLaunchService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class CancelRegistrationLaunchJob implements ShouldBeUnique, ShouldQueue
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
    ) {}

    public function handle(AutomaticRegistrationLaunchService $service): void
    {
        $service->cancelForRegistration($this->registrationType, $this->registrationId);
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
        return 'cancel:'.$this->lockKey();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha ao cancelar lançamento automático de inscrição.', [
            'registration_type' => $this->registrationType,
            'registration_id' => $this->registrationId,
            'error' => $exception?->getMessage(),
        ]);
    }

    private function lockKey(): string
    {
        return "automatic-registration-launch:{$this->registrationType}:{$this->registrationId}";
    }
}
