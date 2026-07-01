<?php

namespace App\Filament\Resources\WaitlistEntries\Pages;

use App\Filament\Resources\WaitlistEntries\WaitlistEntryResource;
use App\Support\Campistas\WaitlistManager;
use Filament\Resources\Pages\CreateRecord;

class CreateWaitlistEntry extends CreateRecord
{
    protected static string $resource = WaitlistEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['telefone_normalizado'] = app(WaitlistManager::class)->normalizePhone($data['telefone'] ?? null);
        $data['accepted_privacy_at'] ??= now();

        return parent::mutateFormDataBeforeCreate($data);
    }
}
