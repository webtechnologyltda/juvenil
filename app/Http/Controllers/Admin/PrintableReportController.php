<?php

namespace App\Http\Controllers\Admin;

use App\Support\Reports\CampistaReportData;
use App\Support\Reports\CampistaReportType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrintableReportController
{
    public function __invoke(Request $request, CampistaReportData $reports)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $type = CampistaReportType::tryFrom((string) $request->query('type'));
        abort_unless($type instanceof CampistaReportType, 404);
        abort_unless($type->canBeAccessedBy($user), 403);

        $report = $reports->payload($type, $request->query(), $user);

        return view('admin.reports.print', [
            'report' => $report,
            'returnUrl' => $this->returnUrl($request->query('return')),
            'logoSrc' => asset('img/logo.png'),
        ]);
    }

    private function returnUrl(mixed $value): string
    {
        $fallback = route('filament.admin.pages.reports-page');

        if (! is_string($value) || blank($value)) {
            return $fallback;
        }

        return Str::startsWith($value, route('filament.admin.pages.reports-page'))
            ? $value
            : $fallback;
    }
}
