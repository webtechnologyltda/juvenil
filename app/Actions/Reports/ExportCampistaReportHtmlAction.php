<?php

namespace App\Actions\Reports;

use App\Models\User;
use App\Support\Reports\CampistaReportData;
use App\Support\Reports\CampistaReportType;
use Illuminate\Support\Str;

class ExportCampistaReportHtmlAction
{
    public function __construct(private readonly CampistaReportData $reports) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataFor(CampistaReportType $type, array $filters, User $user): array
    {
        return [
            'report' => $this->reports->payload($type, $filters, $user),
            'returnUrl' => $this->returnUrl($filters),
            'logoSrc' => asset('img/logo.png'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function output(CampistaReportType $type, array $filters, User $user): string
    {
        return view('admin.reports.print', $this->dataFor($type, $filters, $user))->render();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filenameFor(CampistaReportType $type, array $filters): string
    {
        $suffix = now()->format('Ymd-His');
        $search = filled($filters['search'] ?? null) ? '-'.Str::slug((string) $filters['search']) : '';

        return Str::slug($type->label()).$search.'-'.$suffix.'.html';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function returnUrl(array $filters): string
    {
        $query = collect($filters)
            ->except('_template_version')
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [] && $value !== false)
            ->all();

        return route('filament.admin.pages.reports-page', $query);
    }
}
