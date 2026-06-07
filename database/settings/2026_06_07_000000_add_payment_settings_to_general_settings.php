<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('general', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('valor_acampamento');
            $blueprint->add('pix_copia_cola');
            $blueprint->add('pix_qr_code');
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('general', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('valor_acampamento');
            $blueprint->delete('pix_copia_cola');
            $blueprint->delete('pix_qr_code');
        });
    }
};
