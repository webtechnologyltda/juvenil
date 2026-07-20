<?php

use App\Jobs\SendNewRegistrationNotification;
use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Ramsey\Uuid\Uuid;

uses(RefreshDatabase::class);

it('stores one notification per admin across partial retries', function () {
    $initialRecipients = User::factory()->count(2)->create();

    $job = (new SendNewRegistrationNotification(Campista::class, 501, 'Maria da Silva'))->afterCommit();

    $job->handle();

    $initialNotificationIds = DatabaseNotification::query()->pluck('id')->sort()->values()->all();
    $lateRecipient = User::factory()->create();

    $job->handle();

    expect($job->afterCommit)->toBeTrue()
        ->and(DatabaseNotification::query()->count())->toBe(3)
        ->and(DatabaseNotification::query()->whereIn('notifiable_id', $initialRecipients->modelKeys())->pluck('id')->sort()->values()->all())
        ->toBe($initialNotificationIds)
        ->and($lateRecipient->notifications()->count())->toBe(1)
        ->and(DatabaseNotification::query()->pluck('id')->every(fn (string $id): bool => Uuid::isValid($id)))
        ->toBeTrue();

    $notification = DatabaseNotification::query()->firstOrFail();

    expect($notification->data['title'])->toBe('Nova inscrição')
        ->and($notification->data['body'])->toContain('MARIA DA SILVA')
        ->and($notification->data['body'])->toContain('campista');
});

it('preserves the team registration notification content', function () {
    User::factory()->create();

    (new SendNewRegistrationNotification(EquipeTrabalho::class, 702, 'Jose Souza'))->handle();

    $notification = DatabaseNotification::query()->firstOrFail();

    expect($notification->data['title'])->toBe('Nova inscrição')
        ->and($notification->data['body'])->toContain('JOSE SOUZA')
        ->and($notification->data['body'])->toContain('equipe de trabalho');
});

it('uses registration type and id in the idempotency key', function () {
    User::factory()->create();

    (new SendNewRegistrationNotification(Campista::class, 10, 'Primeira'))->handle();
    (new SendNewRegistrationNotification(Campista::class, 10, 'Primeira'))->handle();
    (new SendNewRegistrationNotification(Campista::class, 11, 'Segunda'))->handle();
    (new SendNewRegistrationNotification(EquipeTrabalho::class, 10, 'Equipe'))->handle();

    expect(DatabaseNotification::query()->count())->toBe(3)
        ->and(DatabaseNotification::query()->pluck('id')->unique())->toHaveCount(3);
});

it('keeps recipients out of the queued payload and public forms', function () {
    $job = new SendNewRegistrationNotification(Campista::class, 801, 'Ana Lima');
    $serializedJob = serialize($job);
    $campistaForm = file_get_contents(app_path('Livewire/CampistaForm.php'));
    $teamForm = file_get_contents(app_path('Livewire/EquipeTrabalhoForm.php'));

    expect($serializedJob)
        ->not->toContain(User::class)
        ->and($campistaForm)
        ->toContain('SendNewRegistrationNotification::dispatch(Campista::class, $campista->id, $campista->nome)')
        ->toContain('->afterCommit()')
        ->toContain('catch (Throwable $exception)')
        ->toContain('rescue(fn () => report($exception), report: false)')
        ->not->toContain('User::all()')
        ->and($teamForm)
        ->toContain('SendNewRegistrationNotification::dispatch(EquipeTrabalho::class, $voluntario->id, $voluntario->nome)')
        ->toContain('->afterCommit()')
        ->toContain('catch (Throwable $exception)')
        ->toContain('rescue(fn () => report($exception), report: false)')
        ->not->toContain('User::all()');
});
