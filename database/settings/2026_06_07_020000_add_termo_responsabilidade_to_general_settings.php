<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('general', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('termo_responsabilidade');
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('general', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('termo_responsabilidade');
        });
    }
};
