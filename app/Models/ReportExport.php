<?php

namespace App\Models;

use App\Enums\ReportExportStatus;
use App\Support\Reports\CampistaReportType;
use Database\Factories\ReportExportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property CampistaReportType $report_type
 * @property ReportExportStatus $status
 * @property string $filters_hash
 * @property array<string, mixed> $filters
 * @property string $disk
 * @property string|null $file_path
 * @property string $filename
 * @property string|null $error_message
 * @property int $progress_current
 * @property int $progress_total
 * @property string|null $progress_message
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ReportExport extends Model
{
    /** @use HasFactory<ReportExportFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'report_type',
        'status',
        'filters_hash',
        'filters',
        'disk',
        'file_path',
        'filename',
        'error_message',
        'progress_current',
        'progress_total',
        'progress_message',
        'started_at',
        'finished_at',
        'expires_at',
    ];

    protected $attributes = [
        'status' => 'pending',
        'disk' => 'local',
        'progress_current' => 0,
        'progress_total' => 100,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_type' => CampistaReportType::class,
            'status' => ReportExportStatus::class,
            'filters' => 'array',
            'progress_current' => 'integer',
            'progress_total' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPendingOrProcessing(): bool
    {
        return in_array($this->status, [ReportExportStatus::Pending, ReportExportStatus::Processing], true);
    }

    public function isReady(): bool
    {
        return $this->status === ReportExportStatus::Ready;
    }

    public function isFailed(): bool
    {
        return $this->status === ReportExportStatus::Failed;
    }

    public function progressPercent(): int
    {
        if ($this->progress_total <= 0) {
            return 0;
        }

        return min(100, (int) round(($this->progress_current / $this->progress_total) * 100));
    }
}
