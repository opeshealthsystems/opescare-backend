<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TriageRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'visit_id',
        'nurse_id',
        'presenting_complaint',
        'pain_score',
        'pregnancy_status',
        'acuity_score',
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function vitalSigns()
    {
        return $this->hasMany(VitalSign::class);
    }

    /**
     * The observations taken AT TRIAGE — `triage_vital_signs`, the
     * `facility_measured` table (App\Enums\VitalProvenance).
     *
     * Deliberately a second relation rather than a change to vitalSigns()
     * above, which points at `vital_signs` and has callers of its own. There is
     * more than one row per record by design: a reassessment APPENDS a fresh
     * set rather than editing the first, so the caller orders and the newest
     * row is "now".
     */
    public function triageVitalSigns()
    {
        return $this->hasMany(TriageVitalSign::class, 'triage_record_id');
    }

    /**
     * Every priority ever assigned to this assessment, each chosen by a person
     * (`scoring_system = 'manual'`). Nothing in this feature computes a score,
     * so this is a history of decisions, not of calculations.
     */
    public function scores()
    {
        return $this->hasMany(TriageScore::class, 'triage_assessment_id');
    }

    /** Re-triage events. The record is amended by accretion, never overwritten. */
    public function reassessments()
    {
        return $this->hasMany(TriageReassessment::class, 'triage_assessment_id');
    }

    /** Append-only audit trail (TriageAudit refuses update and delete). */
    public function audits()
    {
        return $this->hasMany(TriageAudit::class, 'triage_record_id');
    }
}
