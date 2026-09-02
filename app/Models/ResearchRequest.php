<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ResearchRequest — Research Access Programme
 *
 * Initial request submitted by a verified researcher to access
 * de-identified or anonymised OpesCare datasets.
 *
 * Security (NON-NEGOTIABLE):
 * - Only de-identified or anonymised data may be granted.
 * - Requires active ResearcherProfile + active EthicsApproval to submit.
 * - Data Access Committee must review before approval.
 * - Rejected requests CANNOT access data under any circumstances.
 */
class ResearchRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'researcher_profile_id',
        'ethics_approval_id',
        'title',
        'purpose',
        'dataset_type',         // aggregate|de_identified|anonymised
        'requested_fields',
        'status',               // submitted|dac_review|approved|rejected|withdrawn
        'data_access_start',
        'data_access_end',
        'submitted_by',
    ];

    protected $casts = [
        'requested_fields' => 'array',
        'data_access_start' => 'date',
        'data_access_end'   => 'date',
    ];

    public function researcherProfile(): BelongsTo
    {
        return $this->belongsTo(ResearcherProfile::class);
    }

    public function ethicsApproval(): BelongsTo
    {
        return $this->belongsTo(EthicsApproval::class);
    }

    public function dacReviews(): HasMany
    {
        return $this->hasMany(DataAccessCommitteeReview::class, 'research_request_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isActive(): bool
    {
        return $this->isApproved()
            && ($this->data_access_end === null || $this->data_access_end->isFuture());
    }

    public function withdraw(): void
    {
        $this->update(['status' => 'withdrawn']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
