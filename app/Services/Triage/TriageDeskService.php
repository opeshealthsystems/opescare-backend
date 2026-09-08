<?php

namespace App\Services\Triage;

use App\Models\TriageAudit;
use App\Models\TriageReassessment;
use App\Models\TriageRecord;
use App\Models\TriageScore;
use App\Models\TriageVitalSign;
use App\Models\Visit;
use App\Support\TriageAcuity;
use Illuminate\Support\Facades\DB;

/**
 * The triage domain — every WRITE the triage desk performs.
 *
 * The controller is only the facility-scoped web surface; the rules live here,
 * exactly as the prescriber desk keeps them in PrescriptionService.
 *
 * ── THE THREE RULES THIS CLASS ENFORCES ────────────────────────────────────
 *
 * 1. A TRIAGE RECORD IS NEVER OVERWRITTEN. There is no update path in this
 *    class and no save() on an existing record. A patient whose condition
 *    changes gets a REASSESSMENT: a new `triage_reassessments` row, a new
 *    `triage_scores` row and, if the nurse took fresh observations, a new
 *    `triage_vital_signs` row. The original assessment and the original
 *    numbers stay exactly as they were taken, which is the only way the
 *    sequence "she was P4 at 09:10 and P1 at 09:40" can be read afterwards.
 *
 * 2. THE ACUITY ARRIVES FROM THE CALLER. Nothing here derives one. Every
 *    `triage_scores` row this class writes carries `scoring_system = 'manual'`
 *    and `computed_by = 'nurse'`, because that is the truth: a person at the
 *    bedside chose it. See App\Support\TriageAcuity for why there is no
 *    suggestion function anywhere in this feature.
 *
 * 3. ONLY MEASURED VITALS ARE WRITTEN. `triage_vital_signs` is a
 *    `facility_measured` table (App\Enums\VitalProvenance). The desk may show
 *    a nurse what the patient logged at home from `patient_recorded_vitals`,
 *    clearly marked, but those readings reach this class through no argument,
 *    no default and no copy: every number written below comes from the fields
 *    the nurse filled in.
 */
class TriageDeskService
{
    /** Written on every score row: a human chose this. */
    public const SCORING_SYSTEM_MANUAL = 'manual';

    public const COMPUTED_BY_NURSE = 'nurse';

    /**
     * The vitals columns the desk writes. Listed once so the form, the
     * validator, the reassessment snapshot and the write all agree.
     *
     * @var list<string>
     */
    public const VITAL_FIELDS = [
        'temperature', 'pulse_rate', 'respiratory_rate', 'systolic_bp', 'diastolic_bp',
        'oxygen_saturation', 'weight_kg', 'height_cm', 'gcs_score', 'consciousness_level',
    ];

    /** AVPU, as `triage_vital_signs.consciousness_level` is documented on the model. */
    public const CONSCIOUSNESS_LEVELS = ['alert', 'voice', 'pain', 'unresponsive'];

    /**
     * The open encounter this triage belongs to, at THIS facility.
     *
     * A triage is an event within a visit, and `triage_records.visit_id` is NOT
     * NULL, so one must exist. An open visit for this patient at this facility
     * is reused — a patient triaged, then re-triaged in the waiting room, is
     * one attendance, not two. Otherwise a visit is opened.
     *
     * `visit_type` is 'outpatient' because that is a value this table already
     * holds; a new vocabulary word is not invented for a walk-in.
     */
    public function openVisitFor(string $patientId, string $facilityId, ?string $providerId = null): Visit
    {
        $existing = Visit::query()
            ->where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where('status', 'open')
            ->orderByDesc('started_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        return Visit::create([
            'patient_id'  => $patientId,
            'facility_id' => $facilityId,
            'provider_id' => $providerId,
            'visit_type'  => 'outpatient',
            'status'      => 'open',
            'started_at'  => now(),
        ]);
    }

    /**
     * Record one triage assessment.
     *
     * @param array{
     *     visit: Visit,
     *     nurse_id: string,
     *     nurse_role: string|null,
     *     presenting_complaint: string,
     *     pain_score: int|null,
     *     pregnancy_status: string|null,
     *     acuity: string,
     *     vitals: array<string, mixed>
     * } $data
     */
    public function record(array $data): TriageRecord
    {
        $visit  = $data['visit'];
        $acuity = $data['acuity'];

        if (! TriageAcuity::isValid($acuity)) {
            throw new \InvalidArgumentException('A triage acuity must be one of the five priority levels.');
        }

        return DB::transaction(function () use ($data, $visit, $acuity) {
            $record = TriageRecord::create([
                'visit_id'             => $visit->id,
                'nurse_id'             => $data['nurse_id'],
                'presenting_complaint' => $data['presenting_complaint'],
                'pain_score'           => $data['pain_score'] ?? null,
                'pregnancy_status'     => $data['pregnancy_status'] ?? null,
                'acuity_score'         => $acuity,
            ]);

            $this->writeVitals($record, $visit, $data['nurse_id'], $data['vitals'] ?? [], $data['pain_score'] ?? null);

            $this->writeScore($record, $visit, $acuity);

            TriageAudit::record('assessed', [
                'visit_id'          => $visit->id,
                'triage_record_id'  => $record->id,
                'performed_by'      => $data['nurse_id'],
                'performed_by_role' => $data['nurse_role'] ?? null,
                'payload'           => [
                    'acuity'         => $acuity,
                    'scoring_system' => self::SCORING_SYSTEM_MANUAL,
                    'has_vitals'     => $this->hasAnyVital($data['vitals'] ?? []),
                ],
            ]);

            return $record->fresh();
        });
    }

    /**
     * Re-triage a waiting patient.
     *
     * The original record is untouched. What changes is what the record now
     * SAYS, and it says it by accretion: a reassessment row carrying the
     * previous and the new priority and a mandatory reason, a fresh score row,
     * and — when observations were retaken — a fresh vitals row rather than an
     * edit of the first one.
     *
     * @param array{
     *     reassessed_by: string,
     *     role: string|null,
     *     new_priority: string,
     *     reason: string,
     *     vitals: array<string, mixed>,
     *     pain_score: int|null
     * } $data
     */
    public function reassess(TriageRecord $record, array $data): TriageReassessment
    {
        $new = $data['new_priority'];

        if (! TriageAcuity::isValid($new)) {
            throw new \InvalidArgumentException('A triage acuity must be one of the five priority levels.');
        }

        $previous = $this->currentPriority($record);

        return DB::transaction(function () use ($record, $data, $new, $previous) {
            $visit = $record->visit;

            $vitals   = $data['vitals'] ?? [];
            $vitalRow = $this->hasAnyVital($vitals)
                ? $this->writeVitals($record, $visit, $data['reassessed_by'], $vitals, $data['pain_score'] ?? null)
                : null;

            $reassessment = TriageReassessment::create([
                'triage_assessment_id' => $record->id,
                'visit_id'             => $record->visit_id,
                'reassessed_by'        => $data['reassessed_by'],
                // `previous_priority` is NOT NULL. When the original acuity is
                // a legacy value outside the vocabulary it is carried over
                // verbatim rather than rewritten to a level nobody chose.
                'previous_priority'    => (string) ($previous ?? ''),
                'new_priority'         => $new,
                'reason_for_change'    => $data['reason'],
                // A snapshot of exactly what was retaken, or null when nothing
                // was. Never a copy of the earlier readings.
                'new_vital_signs'      => $vitalRow ? $vitalRow->only(self::VITAL_FIELDS) : null,
                'reassessed_at'        => now(),
            ]);

            $this->writeScore($record, $visit, $new);

            TriageAudit::record('reassessed', [
                'visit_id'          => $record->visit_id,
                'triage_record_id'  => $record->id,
                'performed_by'      => $data['reassessed_by'],
                'performed_by_role' => $data['role'] ?? null,
                'clinical_note'     => $data['reason'],
                'payload'           => [
                    'previous_priority' => $previous,
                    'new_priority'      => $new,
                    'escalation'        => TriageAcuity::isEscalation($previous, $new),
                    'vitals_retaken'    => $vitalRow !== null,
                ],
            ]);

            return $reassessment;
        });
    }

    /**
     * What this patient's priority IS right now: the newest reassessment, else
     * the newest score, else the acuity written on the record itself.
     *
     * Read rather than stored, so the queue can never drift from the history
     * that produced it.
     */
    public function currentPriority(TriageRecord $record): ?string
    {
        $latest = TriageReassessment::where('triage_assessment_id', $record->id)
            ->orderByDesc('reassessed_at')
            ->orderByDesc('created_at')
            ->first();

        if ($latest) {
            return $latest->new_priority;
        }

        $score = TriageScore::where('triage_assessment_id', $record->id)
            ->orderByDesc('scored_at')
            ->orderByDesc('created_at')
            ->first();

        return $score?->priority_level ?? $record->acuity_score;
    }

    // ── internals ─────────────────────────────────────────────────────────

    /**
     * One `triage_vital_signs` row from the fields the NURSE typed.
     *
     * Every value is taken from $vitals, which the controller builds from
     * validated request input only. There is no fallback to the patient's own
     * `patient_recorded_vitals` and no merge with a previous row: a blank field
     * stays null, because "not measured" and "the same as last time" are
     * different clinical statements.
     */
    private function writeVitals(TriageRecord $record, ?Visit $visit, string $recordedBy, array $vitals, ?int $painScore): ?TriageVitalSign
    {
        if (! $this->hasAnyVital($vitals) && $painScore === null) {
            return null;
        }

        $attributes = [
            'triage_record_id' => $record->id,
            'visit_id'         => $visit?->id,
            'patient_id'       => $visit?->patient_id,
            'pain_score'       => $painScore,
            'recorded_by'      => $recordedBy,
            'recorded_at'      => now(),
        ];

        foreach (self::VITAL_FIELDS as $field) {
            $value = $vitals[$field] ?? null;
            $attributes[$field] = ($value === '' ? null : $value);
        }

        return TriageVitalSign::create($attributes);
    }

    private function writeScore(TriageRecord $record, ?Visit $visit, string $level): TriageScore
    {
        return TriageScore::create([
            'triage_assessment_id' => $record->id,
            'visit_id'             => $record->visit_id ?? $visit?->id,
            'scoring_system'       => self::SCORING_SYSTEM_MANUAL,
            'priority_level'       => $level,
            'numeric_score'        => null,
            'component_scores'     => null,
            'computed_by'          => self::COMPUTED_BY_NURSE,
            'scored_at'            => now(),
        ]);
    }

    private function hasAnyVital(array $vitals): bool
    {
        foreach (self::VITAL_FIELDS as $field) {
            $value = $vitals[$field] ?? null;

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }
}
