<?php

namespace App\Jobs;

use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Models\User;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\HtmlString;
use Ramsey\Uuid\Uuid;

class SendNewRegistrationNotification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  class-string<Campista|EquipeTrabalho>  $registrationType
     */
    public function __construct(
        public string $registrationType,
        public int $registrationId,
        public string $registrationName,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        User::query()
            ->select('id')
            ->chunkById(100, function (Collection $recipients): void {
                $recipients->each(function (User $recipient): void {
                    $recipient->notifications()->firstOrCreate(
                        ['id' => $this->notificationId($recipient->getKey())],
                        [
                            'type' => FilamentDatabaseNotification::class,
                            'data' => $this->notification()->getDatabaseMessage(),
                            'read_at' => null,
                        ],
                    );
                });
            });
    }

    private function notificationId(int|string $userId): string
    {
        return Uuid::uuid5(
            Uuid::NAMESPACE_URL,
            implode('|', [self::class, $this->registrationType, $this->registrationId, $userId]),
        )->toString();
    }

    private function notification(): Notification
    {
        return Notification::make()
            ->info()
            ->title('Nova inscrição')
            ->body(new HtmlString($this->body()));
    }

    private function body(): string
    {
        if ($this->registrationType === Campista::class) {
            return 'Uma nova inscrição foi enviada, para o(a) campista: <strong>'
                .strtoupper($this->registrationName)
                .'</strong> acesse as inscrições para mais detalhes.';
        }

        return 'Uma nova inscrição para equipe de trabalho foi enviada, para o(a) voluntário: '
            .'<strong>'.strtoupper($this->registrationName).'</strong> '
            .'acesse as inscrições da equipe de trabalho para mais detalhes.';
    }
}
