<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * KpiExport — tracks KPI data export jobs.
 *
 * @property string $id
 * @property string $export_type  csv|json|pdf
 * @property string|null $facility_id
 * @property \Illuminate\Support\Carbon $period_from
 * @property \Illuminate\Support\Carbon $period_to
 * @property array $metric_slugs
 * @property string $status  pending|processing|ready|failed
 * @property string|null $file_path
 * @property string $requested_by
 * @property \Illuminate\Support\Carbon $requested_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $error_message
 */
class KpiExport extends Model
{
    use HasUuids;

    protected $fillable = [
        'export_type',
        'facility_id',
        'period_from',
        'period_to',
        'metric_slugs',
        'status',
        'file_path',
        'requested_by',
        'requested_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'period_from'  => 'date',
        'period_to'    => 'date',
        'metric_slugs' => 'array',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function markReady(string $filePath): void
    {
        $this->update([
            'status'       => 'ready',
            'file_path'    => $filePath,
            'completed_at' => now(),
        ]);
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status'        => 'failed',
            'error_message' => $errorMessage,
            'completed_at'  => now(),
        ]);
    }
}
