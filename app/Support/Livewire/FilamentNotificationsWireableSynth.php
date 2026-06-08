<?php

namespace App\Support\Livewire;

use Filament\Notifications\Collection as FilamentNotificationCollection;
use Livewire\Features\SupportWireables\WireableSynth;

class FilamentNotificationsWireableSynth extends WireableSynth
{
    public function hydrate($value, $meta, $hydrateChild): mixed
    {
        if (($meta['class'] ?? null) === FilamentNotificationCollection::class && ! is_array($value)) {
            $value = [];
        }

        if (($meta['class'] ?? null) === FilamentNotificationCollection::class) {
            $value = collect($value)
                ->filter(fn (mixed $notification): bool => is_array($notification))
                ->map(fn (array $notification): array => $this->sanitizeNotificationPayload($notification))
                ->all();
        }

        return parent::hydrate($value, $meta, $hydrateChild);
    }

    /**
     * @param  array<string, mixed>  $notification
     * @return array<string, mixed>
     */
    private function sanitizeNotificationPayload(array $notification): array
    {
        if (isset($notification['actions']) && is_array($notification['actions'])) {
            $notification['actions'] = array_values(array_filter(
                $notification['actions'],
                fn (mixed $action): bool => is_array($action),
            ));
        }

        return $notification;
    }
}
