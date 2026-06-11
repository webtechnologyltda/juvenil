<?php

namespace App\Observers;

use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Jobs\CancelRegistrationLaunchJob;
use App\Jobs\EnsureRegistrationLaunchJob;
use App\Models\EquipeTrabalho;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Foundation\Bus\PendingDispatch;

class EquipeTrabalhoObserver implements ShouldHandleEventsAfterCommit
{
    public function created(EquipeTrabalho $equipeTrabalho): void
    {
        if ($equipeTrabalho->status !== StatusInscricaoEquipeTrabalho::Pendente) {
            return;
        }

        $this->dispatchAsynchronously(EnsureRegistrationLaunchJob::dispatch($equipeTrabalho::class, (int) $equipeTrabalho->getKey()));
    }

    public function updated(EquipeTrabalho $equipeTrabalho): void
    {
        if (! $equipeTrabalho->wasChanged('status')) {
            return;
        }

        $originalStatus = $equipeTrabalho->getOriginal('status');
        $previousStatus = $originalStatus instanceof StatusInscricaoEquipeTrabalho
            ? $originalStatus
            : StatusInscricaoEquipeTrabalho::tryFrom((int) $originalStatus);

        if ($equipeTrabalho->status === StatusInscricaoEquipeTrabalho::Cancelado) {
            $this->dispatchAsynchronously(CancelRegistrationLaunchJob::dispatch($equipeTrabalho::class, (int) $equipeTrabalho->getKey()));

            return;
        }

        if ($previousStatus === StatusInscricaoEquipeTrabalho::Cancelado && $equipeTrabalho->status === StatusInscricaoEquipeTrabalho::Pendente) {
            $this->dispatchAsynchronously(EnsureRegistrationLaunchJob::dispatch($equipeTrabalho::class, (int) $equipeTrabalho->getKey()));
        }
    }

    private function dispatchAsynchronously(PendingDispatch $dispatch): void
    {
        if (config('queue.default') === 'sync') {
            $dispatch->onConnection('database');
        }
    }
}
