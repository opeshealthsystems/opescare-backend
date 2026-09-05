<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentRequest extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'requesting_facility_id',
        'requesting_user_id',
        'purpose',
        'requested_scope',
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'requested_scope' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function requestingFacility()
    {
        return $this->belongsTo(Facility::class, 'requesting_facility_id');
    }

    public function requestingUser()
    {
        return $this->belongsTo(User::class, 'requesting_user_id');
    }

    /**
     * The ConsentGrant this request produced once approved (if any). A request
     * only records the ask; the grant is what actually authorizes facility
     * access and is therefore the target of a patient-initiated revoke.
     *
     * An ORDERED hasOne, not `latestOfMany()`.
     *
     * `latestOfMany()` builds a one-of-many subquery, and CanBeOneOfMany::ofMany
     * unconditionally adds the model's primary key as the tie-breaker column
     * (`if (! array_key_exists($keyName, $columns)) { $columns[$keyName] = 'MAX'; }`
     * — there is no argument that suppresses it). consent_grants.id is a
     * Postgres `uuid` and there is no `max(uuid)`, so the relation blew up with
     *
     *     SQLSTATE[42883] function max(uuid) does not exist
     *
     * and the patient's own inbox — GET /api/mobile/consent-requests, which
     * eager-loads this relation — returned 500 the moment a single
     * consent_requests row existed. Nothing had ever created one: the table was
     * empty from the day it was migrated in May, so the bug sat unreachable
     * behind a feature gap until the request half of the loop was built.
     *
     * `latest('created_at')` is the same intent without the subquery. It is
     * correct under eager loading too — HasOneOrMany::buildDictionary keeps the
     * query's order and HasOne::match takes the first row per parent, which is
     * the newest grant. A request has one grant in practice, so the "load them
     * all and take the first" cost is nil.
     */
    public function grant()
    {
        return $this->hasOne(ConsentGrant::class, 'consent_request_id')->latest('created_at');
    }
}
