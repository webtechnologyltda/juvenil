<?php

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('general', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('liberacao_inscricoes_equipe_trabalho_status', LiberacaoInscricoesEquipeTrabalhoStatusEnum::LIBERADO);
        });
    }

    public function down(): void
    {
        $this->migrator->inGroup('general', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('liberacao_inscricoes_equipe_trabalho_status');
        });
    }
};
