<?php

namespace App\Models;

use App\Enums\VitalProvenance;
use App\Traits\AppendOnlyPartnerRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An Observation an external partner pushed in over FHIR R4.
 *
 * NOT a facility measurement. See the table migration and
 * App\Enums\VitalProvenance for why this is its own table: a number a partner
 * asserted over an API and a number a nurse took on this ward are the same
 * digits, and only the table they live in keeps them apart.
 *
 * `provenance` and `source_system` are NOT fillable. `provenance` is pinned to
 * 'partner_reported' by the database default; a write path able to pass it
 * would be a write path able to claim a partner reading was measured here.
 */
class PartnerObservation extends Model
{
    use HasFactory, HasUuids;
    use AppendOnlyPartnerRecord;
    use \App\Traits\HasFacilityScope;

    protected $table = 'fhir_partner_observations';

    /** Observation.category values this server accepts. */
    public const KIND_VITAL_SIGNS = 'vital-signs';
    public const KIND_LABORATORY  = 'laboratory';

    protected $fillable = [
        'patient_id',
        'facility_id',
        'integration_client_id',
        'diagnostic_report_id',
        'observation_kind',
        'loinc_code',
        'loinc_display',
        'value_quantity',
        'value_unit',
        'value_string',
        'components',
        'partner_interpretation',
        'reference_range',
        'effective_at',
        'status',
        'supersedes_id',
        'fhir_source',
        'idempotency_key',
    ];

    protected $casts = [
        'components'     => 'array',
        'fhir_source'    => 'array',
        'value_quantity' => 'float',
        'effective_at'   => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function diagnosticReport()
    {
        return $this->belongsTo(PartnerDiagnosticReport::class, 'diagnostic_report_id');
    }

    public function supersedes()
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    /**
     * Always partner-reported. Read through the enum rather than the raw
     * column so every consumer gets the same typed answer and the one place
     * this could ever change is here.
     */
    public function provenance(): VitalProvenance
    {
        return VitalProvenance::PartnerReported;
    }

    /** Live rows only. A superseded or retracted assertion is history. */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['entered-in-error', 'amended']);
    }

    public function scopeForPatient(Builder $query, string $patientId): Builder
    {
        return $query->where('patient_id', $patientId);
    }
}
