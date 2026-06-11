<?php

namespace App\Observers;

use App\Enums\StatusInscricao;
use App\Jobs\CancelRegistrationLaunchJob;
use App\Jobs\EnsureRegistrationLaunchJob;
use App\Models\Campista;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Foundation\Bus\PendingDispatch;

class CampistaObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Campista $campista): void
    {
        if ($campista->status !== StatusInscricao::Pendente) {
            return;
        }

        $this->dispatchAsynchronously(EnsureRegistrationLaunchJob::dispatch($campista::class, (int) $campista->getKey()));
    }

    public function updated(Campista $campista): void
    {
        if (! $campista->wasChanged('status')) {
            return;
        }

        $originalStatus = $campista->getOriginal('status');
        $previousStatus = $originalStatus instanceof StatusInscricao
            ? $originalStatus
            : StatusInscricao::tryFrom((int) $originalStatus);

        if ($campista->status === StatusInscricao::Cancelado) {
            $this->dispatchAsynchronously(CancelRegistrationLaunchJob::dispatch($campista::class, (int) $campista->getKey()));

            return;
        }

        if ($previousStatus === StatusInscricao::Cancelado && $campista->status === StatusInscricao::Pendente) {
            $this->dispatchAsynchronously(EnsureRegistrationLaunchJob::dispatch($campista::class, (int) $campista->getKey()));
        }
    }

    private function dispatchAsynchronously(PendingDispatch $dispatch): void
    {
        if (config('queue.default') === 'sync') {
            $dispatch->onConnection('database');
        }
    }
}
