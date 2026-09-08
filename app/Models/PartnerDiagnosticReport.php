<?php

namespace App\Models;

use App\Enums\VitalProvenance;
use App\Traits\AppendOnlyPartnerRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A DiagnosticReport an external partner pushed in over FHIR R4.
 *
 * Deliberately NOT a `lab_orders` + `lab_results` pair. Those tables mean
 * "this laboratory, working for this facility, released this number" and have
 * no column that could say otherwise. A partner asserting a report is a
 * different claim with a different weight, and merging the two would make the
 * difference unrecoverable.
 */
class PartnerDiagnosticReport extends Model
{
    use HasFactory, HasUuids;
    use AppendOnlyPartnerRecord;
    use \App\Traits\HasFacilityScope;

    protected $table = 'fhir_partner_diagnostic_reports';

    protected $fillable = [
        'patient_id',
        'facility_id',
        'integration_client_id',
        'loinc_code',
        'loinc_display',
        'category',
        'status',
        'conclusion',
        'effective_at',
        'issued_at',
        'supersedes_id',
        'fhir_source',
        'idempotency_key',
    ];

    protected $casts = [
        'fhir_source'  => 'array',
        'effective_at' => 'datetime',
        'issued_at'    => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    /** The analytes this report released, in the order they were posted. */
    public function observations()
    {
        return $this->hasMany(PartnerObservation::class, 'diagnostic_report_id');
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
        return $query->whereNotIn('status', ['entered-in-error', 'amended']);
    }
}
