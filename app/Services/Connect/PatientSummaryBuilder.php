<?php

namespace App\Services\Connect;

use App\Models\Patient;
use Illuminate\Support\Facades\DB;

/**
 * The consented record a Connect partner pulls
 * (GET /api/v1/connect/patients/{health_id}/summary).
 *
 * Built straight from the clinical tables by patient_id. The caller has
 * already resolved the patient inside its environment partition and checked
 * an active consent grant for its own facility; the demo global scope is
 * deliberately not consulted here (it follows the host, not the token).
 *
 * Field names are the ones partners read (OPES Triage RecordSections:
 * drug_name, parameter_name, substance, description, vaccine, ...). Free-text
 * clinical notes are never included. Each section is capped.
 */
final class PatientSummaryBuilder
{
    private const LIMIT = 20;

    /** Lab orders whose results have been released to the record. */
    private const RELEASED_LAB_STATUSES = ['resulted', 'verified', 'released', 'completed'];

    public function build(Patient $patient): array
    {
        $id = (string) $patient->getKey();

        return [
            'recent_visits'      => $this->visits($id),
            'recent_lab_results' => $this->labResults($id),
            'medications'        => $this->medications($id),
            'allergies'          => $this->allergies($id),
            'chronic_conditions' => $this->conditions($id),
            'immunizations'      => $this->immunizations($id),
        ];
    }

    private function visits(string $patientId): array
    {
        $visits = DB::table('visits')
            ->leftJoin('facilities', 'facilities.id', '=', 'visits.facility_id')
            ->where('visits.patient_id', $patientId)
            ->orderByDesc('visits.started_at')
            ->limit(self::LIMIT)
            ->get(['visits.id', 'visits.visit_type', 'visits.status', 'visits.started_at', 'visits.ended_at', 'facilities.name as facility_name']);

        $diagnoses = DB::table('diagnoses')
            ->whereIn('visit_id', $visits->pluck('id')->all())
            ->where('status', '!=', 'entered_in_error')
            ->get(['visit_id', 'display_name'])
            ->groupBy('visit_id');

        return $visits->map(fn ($v) => [
            'visit_id'      => (string) $v->id,
            'visit_type'    => $v->visit_type,
            'status'        => $v->status,
            'facility_name' => $v->facility_name,
            'started_at'    => self::iso($v->started_at),
            'ended_at'      => self::iso($v->ended_at),
            'diagnoses'     => ($diagnoses[$v->id] ?? collect())->pluck('display_name')->values()->all(),
        ])->values()->all();
    }

    private function labResults(string $patientId): array
    {
        return DB::table('lab_results')
            ->join('lab_orders', 'lab_orders.id', '=', 'lab_results.lab_order_id')
            ->where('lab_results.patient_id', $patientId)
            ->whereIn('lab_orders.status', self::RELEASED_LAB_STATUSES)
            ->orderByDesc('lab_results.resulted_at')
            ->limit(self::LIMIT)
            ->get([
                'lab_results.id', 'lab_results.parameter_name', 'lab_results.value', 'lab_results.unit',
                'lab_results.reference_range', 'lab_results.flag', 'lab_results.loinc_code', 'lab_results.resulted_at',
                'lab_orders.test_name', 'lab_orders.test_code',
            ])
            ->map(fn ($r) => [
                'result_id'       => (string) $r->id,
                'parameter_name'  => $r->parameter_name,
                'test_name'       => $r->test_name,
                'test_code'       => $r->test_code,
                'loinc_code'      => $r->loinc_code,
                'value'           => $r->value,
                'unit'            => $r->unit,
                'reference_range' => $r->reference_range,
                'flag'            => $r->flag,
                'flagged'         => $r->flag !== null && ! in_array(strtolower((string) $r->flag), ['', 'n', 'normal'], true),
                'resulted_at'     => self::iso($r->resulted_at),
            ])->values()->all();
    }

    private function medications(string $patientId): array
    {
        return DB::table('prescription_items')
            ->join('prescriptions', 'prescriptions.id', '=', 'prescription_items.prescription_id')
            ->where('prescriptions.patient_id', $patientId)
            ->whereNull('prescriptions.voided_at')
            ->whereNotIn('prescriptions.status', ['cancelled', 'voided', 'void', 'draft', 'superseded'])
            ->whereNotIn('prescription_items.status', ['cancelled', 'discontinued'])
            ->orderByDesc('prescriptions.prescribed_at')
            ->limit(self::LIMIT)
            ->get([
                'prescription_items.id', 'prescription_items.drug_name', 'prescription_items.drug_code',
                'prescription_items.strength', 'prescription_items.dosage_form', 'prescription_items.dose',
                'prescription_items.frequency', 'prescription_items.route', 'prescription_items.duration_days',
                'prescription_items.status', 'prescriptions.prescribed_at', 'prescriptions.expires_at',
            ])
            ->map(fn ($m) => [
                'item_id'       => (string) $m->id,
                'drug_name'     => $m->drug_name,
                'drug_code'     => $m->drug_code,
                'strength'      => $m->strength,
                'dosage_form'   => $m->dosage_form,
                'dose'          => $m->dose,
                'frequency'     => $m->frequency,
                'route'         => $m->route,
                'duration_days' => $m->duration_days !== null ? (int) $m->duration_days : null,
                'status'        => $m->status,
                'prescribed_at' => self::iso($m->prescribed_at),
                'expires_at'    => self::iso($m->expires_at),
            ])->values()->all();
    }

    private function allergies(string $patientId): array
    {
        return DB::table('allergy_records')
            ->where('patient_id', $patientId)
            ->whereNotIn('status', ['inactive', 'resolved', 'entered_in_error', 'refuted'])
            ->orderByDesc('created_at')
            ->limit(self::LIMIT)
            ->get(['id', 'substance', 'severity', 'status', 'created_at'])
            ->map(fn ($a) => [
                'allergy_id'  => (string) $a->id,
                'substance'   => $a->substance,
                'severity'    => $a->severity,
                'status'      => $a->status,
                'recorded_at' => self::iso($a->created_at),
            ])->values()->all();
    }

    private function conditions(string $patientId): array
    {
        return DB::table('problem_lists')
            ->where('patient_id', $patientId)
            ->whereNull('resolved_date')
            ->whereNotIn('status', ['resolved', 'inactive', 'entered_in_error', 'refuted'])
            ->orderByDesc('created_at')
            ->limit(self::LIMIT)
            ->get(['id', 'description', 'icd_code', 'icd_version', 'snomed_code', 'status', 'onset_date'])
            ->map(fn ($c) => [
                'condition_id' => (string) $c->id,
                'name'         => $c->description,
                'description'  => $c->description,
                'icd_code'     => $c->icd_code,
                'icd_version'  => $c->icd_version,
                'snomed_code'  => $c->snomed_code,
                'status'       => $c->status,
                'onset_date'   => $c->onset_date ? substr((string) $c->onset_date, 0, 10) : null,
            ])->values()->all();
    }

    private function immunizations(string $patientId): array
    {
        $local = DB::table('immunization_records')
            ->where('patient_id', $patientId)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['entered-in-error', 'entered_in_error', 'not-done', 'not_done'])
            ->orderByDesc('administered_at')
            ->limit(self::LIMIT)
            ->get(['id', 'vaccine_name', 'vaccine_code', 'vaccine_system', 'dose_number', 'status', 'administered_at'])
            ->map(fn ($i) => [
                'immunization_id' => (string) $i->id,
                'vaccine'         => $i->vaccine_name,
                'vaccine_code'    => $i->vaccine_code,
                'vaccine_system'  => $i->vaccine_system,
                'dose_number'     => $i->dose_number,
                'status'          => $i->status,
                'occurrence_date' => self::iso($i->administered_at),
            ]);

        $partner = DB::table('fhir_partner_immunizations')
            ->where('patient_id', $patientId)
            ->where('status', 'completed')
            ->whereNotIn('id', DB::table('fhir_partner_immunizations')->whereNotNull('supersedes_id')->select('supersedes_id'))
            ->orderByDesc('occurrence_at')
            ->limit(self::LIMIT)
            ->get(['id', 'vaccine_display', 'cvx_code', 'status', 'occurrence_at'])
            ->map(fn ($i) => [
                'immunization_id' => (string) $i->id,
                'vaccine'         => $i->vaccine_display ?: ('CVX ' . $i->cvx_code),
                'vaccine_code'    => $i->cvx_code,
                'vaccine_system'  => 'CVX',
                'dose_number'     => null,
                'status'          => $i->status,
                'occurrence_date' => self::iso($i->occurrence_at),
            ]);

        return $local->concat($partner)
            ->sortByDesc('occurrence_date')
            ->take(self::LIMIT)
            ->values()->all();
    }

    private static function iso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }
}
