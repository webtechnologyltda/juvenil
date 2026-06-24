<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportFileController extends Controller
{
    public function __invoke(Request $request, ReportExport $reportExport): StreamedResponse
    {
        $user = $request->user();

        abort_if($user === null || $reportExport->user_id !== $user->id, 403);
        abort_unless($reportExport->report_type->canBeAccessedBy($user), 403);
        abort_unless($reportExport->isReady() && $reportExport->file_path !== null, 404);

        $disk = Storage::disk($reportExport->disk);

        abort_unless($disk->exists($reportExport->file_path), 404);

        $disposition = HeaderUtils::makeDisposition(
            $request->boolean('inline') ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
            $reportExport->filename,
        );

        return response()->stream(
            static function () use ($disk, $reportExport): void {
                $stream = $disk->readStream((string) $reportExport->file_path);

                if ($stream === false) {
                    return;
                }

                fpassthru($stream);
                fclose($stream);
            },
            200,
            [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => $disposition,
                'Content-Length' => (string) $disk->size($reportExport->file_path),
            ],
        );
    }
}
