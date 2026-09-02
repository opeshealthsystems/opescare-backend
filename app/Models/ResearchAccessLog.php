<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ResearchAccessLog — Module 17 (Research & Data Access Program)
 *
 * Immutable append-only audit log of all data access actions performed
 * by a researcher on an approved dataset.
 *
 * Security constraint: "Audit log maintained" (step 10 of research flow).
 * Records must never be updated or deleted.
 */
class ResearchAccessLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'research_access_request_id',
        'researcher_profile_id',
        'action',                // dataset_viewed|dataset_downloaded|query_executed|export_requested
        'action_context',
        'ip_address',
        'occurred_at',
    ];

    protected $casts = [
        'action_context' => 'array',
        'occurred_at'    => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function researchAccessRequest(): BelongsTo
    {
        return $this->belongsTo(ResearchAccessRequest::class);
    }

    public function researcherProfile(): BelongsTo
    {
        return $this->belongsTo(ResearcherProfile::class);
    }

    // ── Factory ───────────────────────────────────────────────────────────────

    /**
     * Append-only factory. Never update or delete research access log entries.
     */
    public static function record(
        string $requestId,
        string $researcherProfileId,
        string $action,
        ?array $context = null,
        ?string $ipAddress = null
    ): self {
        return static::create([
            'research_access_request_id' => $requestId,
            'researcher_profile_id'      => $researcherProfileId,
            'action'                     => $action,
            'action_context'             => $context,
            'ip_address'                 => $ipAddress,
            'occurred_at'                => now(),
        ]);
    }
}
