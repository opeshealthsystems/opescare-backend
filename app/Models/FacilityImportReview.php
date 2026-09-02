<?php

namespace App\Models;

use App\Enums\FacilityImportReviewStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * An import candidate the importer refused to decide about.
 *
 * OsmFacilityImporter writes these rows; nothing read them until now. 439 of
 * them have been sitting at `pending` since the OpenStreetMap run — each one a
 * question the machine correctly declined to answer, waiting for a person.
 */
class FacilityImportReview extends Model
{
    use HasUuids;

    protected $table = 'facility_import_reviews';

    protected $fillable = [
        'source_system',
        'source_ref',
        'source_attribution',
        'reason',
        'status',
        'candidate_name',
        'candidate_type',
        'candidate_city',
        'candidate_region',
        'latitude',
        'longitude',
        'payload',
        'matched_facility_id',
        'matched_facility_name',
        'match_score',
        'match_distance_m',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'status'           => FacilityImportReviewStatus::class,
        'payload'          => 'array',
        'latitude'         => 'float',
        'longitude'        => 'float',
        'match_score'      => 'float',
        'match_distance_m' => 'integer',
        'reviewed_at'      => 'datetime',
    ];

    public function matchedFacility()
    {
        return $this->belongsTo(CareFacility::class, 'matched_facility_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * 227 of OpenStreetMap's Cameroonian health features carry no `name` tag,
     * and those are the ones a machine must not act on. The reviewer works from
     * the raw tags instead — the name is often sitting in a mistagged key.
     */
    public function displayName(): string
    {
        if (filled($this->candidate_name)) {
            return $this->candidate_name;
        }

        foreach (['operator', 'dispensing', 'brand', 'ref', 'description'] as $tag) {
            if (filled($this->payload[$tag] ?? null)) {
                return (string) $this->payload[$tag];
            }
        }

        return '';
    }
}
