<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionAccessRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'developer_account_id',
        'integration_client_id',
        'use_case',
        'technical_description',
        'requested_scopes',
        'estimated_daily_requests',
        'handles_patient_data',
        'data_residency_region',
        'security_review_done',
        'terms_accepted',
        'terms_version',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'approved_scopes',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'requested_scopes'     => 'array',
        'approved_scopes'      => 'array',
        'handles_patient_data' => 'boolean',
        'security_review_done' => 'boolean',
        'terms_accepted'       => 'boolean',
        'reviewed_at'          => 'datetime',
        'approved_at'          => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function developerAccount(): BelongsTo
    {
        return $this->belongsTo(DeveloperAccount::class);
    }

    // ── Status Helpers ────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve(string $reviewedBy, ?array $approvedScopes = null, ?string $notes = null): void
    {
        $this->update([
            'status'          => 'approved',
            'reviewed_by'     => $reviewedBy,
            'reviewed_at'     => now(),
            'approved_at'     => now(),
            'approved_scopes' => $approvedScopes ?? $this->requested_scopes,
            'review_notes'    => $notes,
        ]);

        // Promote the developer account off sandbox-only
        $this->developerAccount?->activate();
    }

    public function reject(string $reviewedBy, string $reason): void
    {
        $this->update([
            'status'           => 'rejected',
            'reviewed_by'      => $reviewedBy,
            'reviewed_at'      => now(),
            'rejected_reason'  => $reason,
        ]);
    }

    public function startReview(string $reviewedBy): void
    {
        $this->update([
            'status'      => 'under_review',
            'reviewed_by' => $reviewedBy,
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'approved'     => 'badge badge--success',
            'pending'      => 'badge badge--warning',
            'under_review' => 'badge badge--info',
            'rejected'     => 'badge badge--danger',
            'revoked'      => 'badge badge--neutral',
            default        => 'badge badge--neutral',
        };
    }
}
