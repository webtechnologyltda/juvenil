<?php

namespace Database\Factories;

use App\Enums\ReportExportStatus;
use App\Enums\StatusInscricao;
use App\Models\ReportExport;
use App\Models\User;
use App\Support\Reports\CampistaReportType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportExport>
 */
class ReportExportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = [
            StatusInscricao::Pendente->value,
            StatusInscricao::Pago->value,
        ];

        return [
            'user_id' => User::factory(),
            'report_type' => CampistaReportType::RegistrationFichas,
            'status' => ReportExportStatus::Pending,
            'filters_hash' => hash('sha256', $this->faker->uuid()),
            'filters' => [
                'status' => $status,
            ],
            'disk' => config('filesystems.default'),
            'file_path' => null,
            'filename' => 'fichas-de-inscricao.html',
            'progress_current' => 0,
            'progress_total' => 100,
            'progress_message' => 'Na fila para geração.',
            'expires_at' => now()->addDays(2),
        ];
    }

    public function ready(): self
    {
        return $this->state(fn (): array => [
            'status' => ReportExportStatus::Ready,
            'file_path' => 'report-exports/test.html',
            'progress_current' => 100,
            'progress_total' => 100,
            'progress_message' => 'Relatório pronto.',
            'finished_at' => now(),
        ]);
    }
}
