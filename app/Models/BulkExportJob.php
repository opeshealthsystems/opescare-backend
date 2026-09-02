<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * BulkExportJob
 *
 * Tracks the lifecycle of an async FHIR $export request.
 *
 * Status transitions:
 *   queued → processing → complete
 *                       → failed
 *   complete → expired  (after expires_at, download links no longer served)
 *
 * Created by FhirController::bulkExport() when a $export request is received.
 * Processed by FhirBulkExportJob (queued worker).
 * Status polled by BulkExportController::status().
 * Files served by BulkExportController::download().
 *
 * @property string      $id
 * @property string      $facility_id
 * @property string|null $requested_by    integration_client_id
 * @property string      $status          queued|processing|complete|failed|expired
 * @property int         $progress        0-100
 * @property array|null  $parameters      original $export query params
 * @property array|null  $output_files    [{type, url, count}] when complete
 * @property string|null $error
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon      $created_at
 * @property \Carbon\Carbon      $updated_at
 */
class BulkExportJob extends Model
{
    use HasUuids;

    protected $table = 'bulk_export_jobs';

    protected $fillable = [
        'facility_id',
        'requested_by',
        'status',
        'progress',
        'parameters',
        'output_files',
        'error',
        'expires_at',
        'completed_at',
    ];

    protected $casts = [
        'parameters'   => 'array',
        'output_files' => 'array',
        'expires_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForFacility($query, string $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expires_at && $this->expires_at->isPast());
    }

    public function isStillProcessing(): bool
    {
        return in_array($this->status, ['queued', 'processing'], true);
    }
}
