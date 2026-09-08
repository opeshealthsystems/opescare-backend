<?php

namespace App\Models;

use App\Enums\VitalProvenance;
use App\Traits\AppendOnlyPartnerRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An Immunization an external partner pushed in over FHIR R4.
 *
 * `immunization_records` carries `administered_by_id` and a
 * `verification_status` that a person at this facility moves. A partner push
 * has no administering user here and nobody here to verify it, so it is kept
 * apart rather than filed as an unverified local administration - a row in
 * the local table with a null administrator reads as an incomplete local
 * record, not as an external claim, and the two are not the same thing.
 */
class PartnerImmunization extends Model
{
    use HasFactory, HasUuids;
    use AppendOnlyPartnerRecord;
    use \App\Traits\HasFacilityScope;

    protected $table = 'fhir_partner_immunizations';

    protected $fillable = [
        'patient_id',
        'facility_id',
        'integration_client_id',
        'cvx_code',
        'vaccine_display',
        'status',
        'occurrence_at',
        'lot_number',
        'manufacturer',
        'dose_quantity',
        'dose_unit',
        'route_code',
        'site_code',
        'supersedes_id',
        'fhir_source',
        'idempotency_key',
    ];

    protected $casts = [
        'fhir_source'    => 'array',
        'occurrence_at'  => 'datetime',
        'dose_quantity'  => 'float',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function supersedes()
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    public function provenance(): VitalProvenance
    {
        return VitalProvenance::PartnerReported;
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['entered-in-error']);
    }
}
