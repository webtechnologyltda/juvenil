<?php

use App\Support\Livewire\FilamentNotificationsWireableSynth;
use Filament\Notifications\Collection as FilamentNotificationCollection;
use Filament\Notifications\Notification;
use Livewire\Component;
use Livewire\Mechanisms\HandleComponents\ComponentContext;
use Livewire\Mechanisms\HandleComponents\HandleComponents;

it('sanitizes malformed filament notification payloads during Livewire hydration', function () {
    $synth = new FilamentNotificationsWireableSynth(
        new ComponentContext(new class extends Component {}),
        'notifications',
    );

    $collection = $synth->hydrate(
        [
            0,
            'valid-notification' => Notification::make('valid-notification')
                ->title('Inscrição recebida')
                ->success()
                ->toArray(),
        ],
        ['class' => FilamentNotificationCollection::class],
        fn (string|int $key, mixed $child): mixed => $child,
    );

    expect($collection)
        ->toBeInstanceOf(FilamentNotificationCollection::class)
        ->toHaveCount(1)
        ->and($collection->first())
        ->toBeInstanceOf(Notification::class)
        ->getTitle()->toBe('Inscrição recebida');
});

it('hydrates an empty notification collection when the whole notification payload is malformed', function () {
    $synth = new FilamentNotificationsWireableSynth(
        new ComponentContext(new class extends Component {}),
        'notifications',
    );

    $collection = $synth->hydrate(
        0,
        ['class' => FilamentNotificationCollection::class],
        fn (string|int $key, mixed $child): mixed => $child,
    );

    expect($collection)
        ->toBeInstanceOf(FilamentNotificationCollection::class)
        ->toBeEmpty();
});

it('registers the safe wireable synthesizer before Livewire hydrates Filament notifications', function () {
    $synth = app(HandleComponents::class)->findSynth(
        new FilamentNotificationCollection,
        new class extends Component {},
    );

    expect($synth)->toBeInstanceOf(FilamentNotificationsWireableSynth::class);
});
