<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * One line per FHIR ingest attempt - accepted or refused, and why.
 *
 * A refused write leaves no clinical row by definition, so without this table
 * the two events that matter most at this door leave no trace at all: a
 * partner writing against a Health ID that does not resolve, and a partner
 * writing without the patient's consent.
 *
 * The submitted Health ID is stored HASHED. This log must not itself become a
 * list of identifiers to try.
 */
class FhirIngestEvent extends Model
{
    use HasUuids;

    protected $table = 'fhir_ingest_events';

    public const UPDATED_AT = null;

    public const OUTCOME_ACCEPTED = 'accepted';
    public const OUTCOME_REFUSED  = 'refused';

    protected $fillable = [
        'integration_client_id',
        'facility_id',
        'patient_id',
        'resource_type',
        'outcome',
        'refusal_code',
        'refusal_detail',
        'http_status',
        'subject_identifier_hash',
        'idempotency_key',
        'created_resource_id',
        'created_at',
    ];

    protected $casts = [
        'created_at'  => 'datetime',
        'http_status' => 'integer',
    ];

    /** The one-way fingerprint of a submitted Health ID. */
    public static function hashIdentifier(?string $identifier): ?string
    {
        $value = strtoupper(trim((string) $identifier));

        return $value === '' ? null : hash('sha256', $value);
    }
}
