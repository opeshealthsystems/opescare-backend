<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResearchAccessRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'requesting_organization',
        'principal_investigator',
        'purpose',
        'ethics_document_id',
        'requested_dataset_scope_json',
        'status',
        'reviewed_by',
        'approved_at',
        'expires_at',
    ];

    protected $casts = [
        'requested_dataset_scope_json' => 'array',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Data Access Committee reviews for this request.
     *
     * ResearchAccessController::show() has always eager-loaded this relation
     * and `dataAgreements`, but neither was defined here — so every call to
     * GET /v1/research/requests/{id} threw before it reached the response.
     * Both tables carry `research_access_request_id`, so the relations were
     * simply never written. (Note the similarly named ResearchRequest model,
     * which defines its own dacReviews() against a different table — easy to
     * mistake one for the other.)
     */
    public function dacReviews()
    {
        return $this->hasMany(DataAccessCommitteeReview::class, 'research_access_request_id');
    }

    /** Signed data-use agreements covering this request. */
    public function dataAgreements()
    {
        return $this->hasMany(ResearchDataAgreement::class, 'research_access_request_id');
    }

    /** Every access event recorded against this request. */
    public function accessLogs()
    {
        return $this->hasMany(ResearchAccessLog::class, 'research_access_request_id');
    }
}
