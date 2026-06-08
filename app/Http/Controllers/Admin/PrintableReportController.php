<?php

namespace App\Http\Controllers\Admin;

use App\Support\Reports\CampistaReportData;
use App\Support\Reports\CampistaReportType;
use Illuminate\Http\Request;

class PrintableReportController
{
    public function __invoke(Request $request, CampistaReportData $reports)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $type = CampistaReportType::tryFrom((string) $request->query('type'));
        abort_unless($type instanceof CampistaReportType, 404);
        abort_unless($type->canBeAccessedBy($user), 403);

        return view('admin.reports.print', [
            'report' => $reports->payload($type, $request->query(), $user),
        ]);
    }
}
