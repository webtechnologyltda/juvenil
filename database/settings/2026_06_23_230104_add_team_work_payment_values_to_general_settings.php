<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('general', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('valor_equipe_trabalho_interna', 0);
            $blueprint->add('valor_equipe_trabalho_externa', 0);
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('general', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('valor_equipe_trabalho_interna');
            $blueprint->delete('valor_equipe_trabalho_externa');
        });
    }
};
