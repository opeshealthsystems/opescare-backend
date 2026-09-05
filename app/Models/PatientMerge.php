<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PatientMerge — the record of one identity merge, exact enough to undo.
 *
 * `manifest` lists every row that changed hands, by table, column and id, and
 * every row that was deliberately left behind because the survivor already
 * held a one-per-patient equivalent. `survivorship` lists the demographic
 * fields that were NULL on the survivor and took the retired record's value.
 *
 * An unmerge reads this and nothing else: rows the survivor acquired AFTER the
 * merge are not in the manifest and stay where they are.
 */
class PatientMerge extends Model
{
    use HasUuids;

    public const STATUS_MERGED   = 'merged';
    public const STATUS_UNMERGED = 'unmerged';

    protected $fillable = [
        'candidate_id',
        'survivor_patient_id',
        'retired_patient_id',
        // Plain-text snapshots: the audit trail after a purge SET NULLs the keys.
        'survivor_health_id',
        'retired_health_id',
        'merged_by',
        'reason',
        'manifest',
        'survivorship',
        'retired_previous_identity_status',
        'status',
        'unmerged_by',
        'unmerged_at',
        'unmerge_reason',
    ];

    protected $casts = [
        'manifest'     => 'array',
        'survivorship' => 'array',
        'unmerged_at'  => 'datetime',
    ];

    public function survivor(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'survivor_patient_id')->withoutGlobalScope('isolate_demo');
    }

    public function retired(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'retired_patient_id')->withoutGlobalScope('isolate_demo');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(MpiCandidate::class, 'candidate_id');
    }

    public function mergedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by');
    }

    public function isMerged(): bool
    {
        return $this->status === self::STATUS_MERGED;
    }

    /** Rows that changed hands, across every table. */
    public function movedRowCount(): int
    {
        return array_sum(array_map(fn (array $e) => count($e['moved'] ?? []), $this->manifest ?? []));
    }

    /** Rows left on the retired record because the survivor already had one. */
    public function keptRowCount(): int
    {
        return array_sum(array_map(fn (array $e) => count($e['kept'] ?? []), $this->manifest ?? []));
    }
}
