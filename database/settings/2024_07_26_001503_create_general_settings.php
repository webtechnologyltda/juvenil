<?php

use App\Enums\LiberacaoInscricoesStatusEnum;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('general', function (SettingsBlueprint $blueprint): void {
            $blueprint->add('telefone_atendente');
            $blueprint->add('qtd_max_vagas');
            $blueprint->add('qtd_max_vagas_feminino');
            $blueprint->add('qtd_max_vagas_masculino');
            $blueprint->add('data_inicio_inscricoes');
            $blueprint->add('data_final_inscricoes');
            $blueprint->add('liberacao_inscricoes_status', LiberacaoInscricoesStatusEnum::LIBERADO);
            $blueprint->add('liberacao_inscricoes_bloco');
        });

    }

    public function down(): void
    {
        $this->migrator->inGroup('general', function (SettingsBlueprint $blueprint): void {
            $blueprint->delete('telefone_atendente');
            $blueprint->delete('qtd_max_vagas');
            $blueprint->delete('qtd_max_vagas_feminino');
            $blueprint->delete('qtd_max_vagas_masculino');
            $blueprint->delete('data_inicio_inscricoes');
            $blueprint->delete('data_final_inscricoes');
            $blueprint->delete('liberacao_inscricoes_status');
            $blueprint->delete('liberacao_inscricoes_equipe_trabalho_status');
            $blueprint->delete('liberacao_inscricoes_bloco');
        });
    }
};
