<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AllergyRecord;
use App\Models\Appointment;
use App\Models\CareTeamMember;
use App\Models\ClinicalNote;
use App\Models\ConsentGrant;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\ImagingOrder;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\ProblemList;
use App\Models\RadiologyReport;
use App\Models\TriageVitalSign;
use App\Models\User;
use App\Models\VitalSign;
use App\Modules\Governance\Services\AccessLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mobile Provider API — the READ-ONLY patient chart.
 *
 * Serves `/api/mobile/doctor/patients/{id}` and its seven sections. Every route
 * sits behind `auth.provider` (AuthenticateMobileProvider), so:
 *
 *     clinician  ← $request->attributes 'provider_user_id'
 *     facility   ← $request->attributes 'provider_facility_id'
 *
 * Both are stamped by the middleware from the bearer token. Neither is ever
 * read from the body, the query string, a header or the session, and there is
 * no parameter anywhere in this file that would let a caller name either one.
 * A `facility_id` posted in the body is simply not looked at.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE ACCESS RULE — WHY A CLINICIAN MAY OPEN *THIS* CHART
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Holding a patient's id is NOT authorisation, and neither is holding a valid
 * provider token. A chart read is a disclosure of someone else's record, so it
 * has to be justified by a clinical relationship that already exists in the
 * data. On every single request this controller resolves ONE of the following
 * bases, in order, and refuses with 403 when none of them holds:
 *
 *   1. `appointment`          — the patient is on THIS clinician's schedule:
 *                               an appointment whose provider_id is them.
 *   2. `care_team`            — they are a current member of the patient's
 *                               care team (care_team_members, still active).
 *   3. `treating_clinician`   — they personally performed a clinical act for
 *                               this patient: attending on an encounter,
 *                               provider on a visit, prescriber, or the
 *                               clinician who raised a lab / imaging order.
 *   4. `facility_episode`     — the patient has an episode of care at the
 *                               clinician's OWN facility (the token's
 *                               facility, never a named one) that is either
 *                               still open, scheduled in the future, or began
 *                               within FACILITY_EPISODE_LOOKBACK_DAYS.
 *   5. `consent_grant`        — the patient has granted this facility an
 *                               active, unexpired ConsentGrant.
 *
 * Nothing else opens a chart. There is deliberately no "emergency" or
 * "break-glass" path here: the platform has EmergencyAccessService for that,
 * it is a different act with a different audit signature, and quietly bolting
 * it onto a read endpoint would make the refusal below meaningless.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * EVERY READ IS A DISCLOSURE, AND EVERY DISCLOSURE IS LOGGED
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Each successful section read writes one patient-scoped `access_logs` row via
 * AccessLogService, carrying the purpose (`treatment:<basis>` — so the audit
 * says not only WHY the record was opened but on WHAT AUTHORITY) and the data
 * category actually disclosed. Unlike the best-effort audit on the patient's
 * own export, an audit failure here FAILS THE READ (503): a clinician reading
 * another person's record with no trail is precisely the event this platform
 * exists to prevent, so the disclosure does not happen if it cannot be
 * recorded. Refusals are logged too (access_type `denied`), best-effort —
 * a refusal discloses nothing, so it must never itself become an error.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * NO ENUMERATION, NO FABRICATION
 * ─────────────────────────────────────────────────────────────────────────
 *
 * There is no listing endpoint and no search of any kind in this file. A chart
 * is addressed by an EXACT patient id or an EXACT Health ID and by nothing
 * else — no LIKE, no prefix, no name. Finding a patient in the first place is
 * MobilePrescribingController::lookupPatient's job (exact Health ID, one row
 * or 404) and is deliberately not duplicated here.
 *
 * Where the Finder schema simply does not record a field the clinician chart
 * contract carries — an allergy's reaction, an encounter's assessment, BMI —
 * this returns `null`. Null means "not recorded". It is never filled with a
 * plausible value, never derived, and never carried forward from another row.
 */
class MobileDoctorChartController extends Controller
{
    /**
     * How far back an episode at the clinician's own facility still counts as
     * a live care relationship. Older, closed episodes need one of the other
     * four bases — most naturally a new appointment, which is basis 1.
     */
    private const FACILITY_EPISODE_LOOKBACK_DAYS = 365;

    /**
     * Hard ceiling on rows returned by a scan-bounded section. Reaching it is
     * the ONLY thing that sets `truncated` — see scanResult().
     */
    private const SCAN_HARD_CAP = 500;

    /** Default / maximum page size for the paginated encounter list. */
    private const ENCOUNTERS_PER_PAGE = 20;
    private const ENCOUNTERS_MAX_PER_PAGE = 100;

    /** Default / maximum number of vitals recordings returned. */
    private const VITALS_LIMIT = 20;
    private const VITALS_MAX_LIMIT = 100;

    // ══════════════════════════════════════════════════════════════════
    // Endpoints
    // ══════════════════════════════════════════════════════════════════

    /**
     * GET /api/mobile/doctor/patients/{id}
     *
     * The chart header: who this is, how to reach them, whether there are
     * allergies to open, and whether this facility holds a consent grant.
     * No clinical content — every section below is its own request, its own
     * audit row and its own data category.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $facilityId = $this->facilityId($request);

        return $this->chart($request, $id, 'patient_identity', 'Patient', function (Patient $patient) use ($facilityId) {
            return $this->formatPatient($patient, $this->hasActiveConsentGrant($patient->id, $facilityId));
        });
    }

    /**
     * GET /api/mobile/doctor/patients/{id}/allergies
     *
     * Never folded into anything else. An allergy list that fails inside an
     * aggregate renders as "no known allergies", which is the single most
     * dangerous thing a chart can say — so it is one endpoint, one failure.
     */
    public function allergies(Request $request, string $id): JsonResponse
    {
        return $this->chart($request, $id, 'allergies', 'AllergyRecord', function (Patient $patient) {
            $rows = AllergyRecord::where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->limit(self::SCAN_HARD_CAP)
                ->get();

            return $rows->map(fn (AllergyRecord $a) => [
                'id'           => $a->id,
                'patient_id'   => $a->patient_id,
                // allergy_records has no type column on this deployment.
                // Null is "not recorded"; it is not guessed as `drug`.
                'allergy_type' => null,
                'allergen'     => $a->substance,
                'severity'     => $a->severity,
                'reaction'     => null,
                'onset_date'   => null,
                'notes'        => null,
                'recorded_by'  => $a->provider_id,
                'created_at'   => $this->iso($a->created_at),
                'updated_at'   => $this->iso($a->updated_at),
            ])->values();
        });
    }

    /**
     * GET /api/mobile/doctor/patients/{id}/problems
     *
     * The standing problem list — not the diagnoses coded at a single visit,
     * which are their own section.
     */
    public function problems(Request $request, string $id): JsonResponse
    {
        return $this->chart($request, $id, 'problem_list', 'ProblemList', function (Patient $patient) {
            $rows = ProblemList::where('patient_id', $patient->id)
                ->orderByRaw('onset_date DESC NULLS LAST')
                ->orderByDesc('created_at')
                ->limit(self::SCAN_HARD_CAP)
                ->get();

            return $rows->map(fn (ProblemList $p) => [
                'id'                => $p->id,
                'patient_id'        => $p->patient_id,
                'icd10_code'        => $p->icd_code,
                'icd10_description' => $p->description,
                'onset_date'        => $p->onset_date?->toDateString(),
                'status'            => $p->status,
                'notes'             => $p->notes,
                'added_by'          => $p->provider_id,
                'created_at'        => $this->iso($p->created_at),
            ])->values();
        });
    }

    /**
     * GET /api/mobile/doctor/patients/{id}/encounters?page=&per_page=
     *
     * Paginated, newest first. The SOAP body comes from the visit's clinical
     * note; `assessment` has no column in this schema and is therefore null,
     * not a copy of something adjacent.
     */
    public function encounters(Request $request, string $id): JsonResponse
    {
        $perPage = $this->boundedInt($request->query('per_page'), self::ENCOUNTERS_PER_PAGE, 1, self::ENCOUNTERS_MAX_PER_PAGE);

        return $this->chart(
            $request,
            $id,
            'encounters',
            'Encounter',
            function (Patient $patient) use ($perPage) {
                $page = Encounter::where('patient_id', $patient->id)
                    ->orderByDesc('started_at')
                    ->paginate($perPage);

                /** @var \Illuminate\Support\Collection<int,Encounter> $rows */
                $rows = $page->getCollection();

                $notes      = $this->latestNotesByVisit($rows->pluck('visit_id')->filter()->all());
                $doctors    = $this->userNames($rows->pluck('attending_provider_id')->filter()->all());
                $facilities = $this->facilityNames($rows->pluck('facility_id')->filter()->all());

                return [
                    'payload' => $rows->map(function (Encounter $e) use ($notes, $doctors, $facilities) {
                        $note = $e->visit_id ? ($notes[$e->visit_id] ?? null) : null;

                        return [
                            'id'              => $e->id,
                            'patient_id'      => $e->patient_id,
                            'facility_id'     => $e->facility_id,
                            'facility_name'   => $facilities[$e->facility_id] ?? null,
                            'encounter_type'  => $e->encounter_type,
                            'status'          => $e->status,
                            'encounter_date'  => $e->started_at?->toDateString(),
                            'encounter_time'  => $e->started_at?->format('H:i:s'),
                            'doctor_id'       => $e->attending_provider_id,
                            'doctor_name'     => $doctors[$e->attending_provider_id] ?? null,
                            'chief_complaint' => $e->reason_for_encounter,
                            // The visit's clinical note, mapped field for field.
                            // There is no `assessment` column anywhere in this
                            // schema, so it stays null rather than borrowing
                            // the treatment plan.
                            'subjective'      => $note?->history_of_present_illness,
                            'objective'       => $note?->examination_findings,
                            'assessment'      => null,
                            'plan'            => $note?->treatment_plan,
                            'discharge_note'  => $e->discharge_disposition,
                            'follow_up_date'  => null,
                            'closed_at'       => $this->iso($e->ended_at),
                            'created_at'      => $this->iso($e->created_at),
                            'updated_at'      => $this->iso($e->updated_at),
                        ];
                    })->values(),
                    'meta' => [
                        'pagination' => [
                            'current_page' => $page->currentPage(),
                            'per_page'     => $page->perPage(),
                            'total'        => $page->total(),
                            'last_page'    => $page->lastPage(),
                            'from'         => $page->firstItem(),
                            'to'           => $page->lastItem(),
                        ],
                    ],
                ];
            },
            true
        );
    }

    /**
     * GET /api/mobile/doctor/patients/{id}/vitals?limit=
     *
     * Newest first, one row per recording SET. Two sources, exactly as
     * MobileVitalsController documents them: `triage_vital_signs`, which is
     * patient-scoped and carries a real `recorded_at`, and the older
     * `vital_signs`, which has neither and is reached through
     * triage_records → visits.
     *
     * BMI, blood glucose and head circumference have no column in either
     * table. They are returned as null — BMI is NOT computed from weight and
     * height, because a derived number presented beside recorded ones reads as
     * a measurement that was taken.
     */
    public function vitals(Request $request, string $id): JsonResponse
    {
        $limit = $this->boundedInt($request->query('limit'), self::VITALS_LIMIT, 1, self::VITALS_MAX_LIMIT);

        return $this->chart($request, $id, 'vitals', 'VitalSign', function (Patient $patient) use ($limit) {
            $rows = [];

            $triage = TriageVitalSign::where('patient_id', $patient->id)
                ->orderByDesc('recorded_at')
                ->limit($limit)
                ->get();

            $legacy = VitalSign::query()
                ->join('triage_records', 'triage_records.id', '=', 'vital_signs.triage_record_id')
                ->join('visits', 'visits.id', '=', 'triage_records.visit_id')
                ->where('visits.patient_id', $patient->id)
                ->orderByDesc('vital_signs.created_at')
                ->limit($limit)
                ->select('vital_signs.*', 'triage_records.visit_id as source_visit_id')
                ->get();

            $visitIds = $triage->pluck('visit_id')->filter()
                ->merge($legacy->pluck('source_visit_id')->filter())
                ->unique()->all();
            $encounterByVisit = $this->encounterIdsByVisit($visitIds);

            foreach ($triage as $v) {
                $rows[] = [
                    'id'                 => $v->id,
                    'encounter_id'       => $v->visit_id ? ($encounterByVisit[$v->visit_id] ?? null) : null,
                    'patient_id'         => $v->patient_id,
                    'bp_systolic'        => $v->systolic_bp,
                    'bp_diastolic'       => $v->diastolic_bp,
                    'temperature'        => $v->temperature,
                    'pulse'              => $v->pulse_rate,
                    'respiratory_rate'   => $v->respiratory_rate,
                    'spo2'               => $v->oxygen_saturation,
                    'weight'             => $v->weight_kg,
                    'height'             => $v->height_cm,
                    'bmi'                => null,
                    'blood_glucose'      => null,
                    'head_circumference' => null,
                    'recorded_by'        => $v->recorded_by,
                    'recorded_at'        => $this->iso($v->recorded_at),
                    'created_at'         => $this->iso($v->created_at),
                ];
            }

            foreach ($legacy as $v) {
                $rows[] = [
                    'id'                 => $v->id,
                    'encounter_id'       => $v->source_visit_id ? ($encounterByVisit[$v->source_visit_id] ?? null) : null,
                    'patient_id'         => $patient->id,
                    'bp_systolic'        => $v->blood_pressure_systolic,
                    'bp_diastolic'       => $v->blood_pressure_diastolic,
                    'temperature'        => $v->temperature,
                    'pulse'              => $v->pulse,
                    'respiratory_rate'   => $v->respiratory_rate,
                    'spo2'               => $v->oxygen_saturation,
                    'weight'             => $v->weight,
                    'height'             => $v->height,
                    'bmi'                => null,
                    'blood_glucose'      => null,
                    'head_circumference' => null,
                    'recorded_by'        => null,
                    // `vital_signs` has no recorded_at. Rather than pass
                    // created_at off as the reading time, this stays null and
                    // the client falls back to created_at knowingly.
                    'recorded_at'        => null,
                    'created_at'         => $this->iso($v->created_at),
                ];
            }

            // Both sources merged into one newest-first series.
            usort($rows, fn (array $a, array $b) => strcmp(
                (string) ($b['recorded_at'] ?? $b['created_at'] ?? ''),
                (string) ($a['recorded_at'] ?? $a['created_at'] ?? '')
            ));

            return array_slice($rows, 0, $limit);
        });
    }

    /**
     * GET /api/mobile/doctor/patients/{id}/prescriptions?scan=
     *
     * One row per prescribed drug (a `prescription_items` line), newest first.
     * `status` is the prescription's own word — `voided`, `dispensed` — passed
     * through verbatim rather than squeezed into the four-value vocabulary the
     * client's type suggests; the client's status unions are open and its
     * labeller title-cases anything it does not recognise, so the truth
     * survives the trip.
     *
     * On `scan`: see scanResult(). This server reads the patient's record
     * directly and does not walk encounters, so the list is COMPLETE and
     * `truncated` is false — the notice that would tell a clinician the list is
     * short stays silent because it would be false, not because nothing checks.
     */
    public function prescriptions(Request $request, string $id): JsonResponse
    {
        return $this->chart($request, $id, 'medications', 'Prescription', function (Patient $patient) {
            $prescriptions = Prescription::where('patient_id', $patient->id)
                ->orderByDesc('prescribed_at')
                ->limit(self::SCAN_HARD_CAP)
                ->get();

            $items = PrescriptionItem::whereIn('prescription_id', $prescriptions->pluck('id')->all())
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('prescription_id');

            $encounterByVisit = $this->encounterIdsByVisit($prescriptions->pluck('visit_id')->filter()->unique()->all());
            $prescriberNames  = $this->userNames($prescriptions->pluck('prescribed_by')->filter()->unique()->all());

            $rows = [];

            foreach ($prescriptions as $script) {
                foreach ($items->get($script->id, collect()) as $item) {
                    $rows[] = [
                        'id'                 => $item->id,
                        'encounter_id'       => $script->visit_id ? ($encounterByVisit[$script->visit_id] ?? null) : null,
                        'patient_id'         => $script->patient_id,
                        'drug_name'          => $item->drug_name,
                        'dosage'             => $item->dose,
                        'route'              => $item->route,
                        'frequency'          => $item->frequency,
                        'duration'           => $item->duration_days === null
                            ? null
                            : __('doctor_chart.duration_days', ['days' => $item->duration_days]),
                        // The prescriber's note on the script. There is no
                        // per-line instructions column; dispense_notes belongs
                        // to the pharmacy and is not the prescriber speaking.
                        'instructions'       => $script->notes,
                        'status'             => $script->status,
                        'prescribed_by'      => $script->prescribed_by,
                        'prescribed_by_name' => $prescriberNames[$script->prescribed_by] ?? null,
                        'created_at'         => $this->iso($item->created_at),
                        'updated_at'         => $this->iso($item->updated_at),
                    ];
                }
            }

            return $this->scanResult($rows, count($prescriptions));
        });
    }

    /**
     * GET /api/mobile/doctor/patients/{id}/diagnoses?scan=
     *
     * The diagnoses coded at the patient's visits. `add_to_problem_list` is
     * not a stored flag on this deployment, so it is answered honestly: true
     * when a problem-list entry actually carries the same code.
     */
    public function diagnoses(Request $request, string $id): JsonResponse
    {
        return $this->chart($request, $id, 'diagnoses', 'Diagnosis', function (Patient $patient) {
            $rows = Diagnosis::where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->limit(self::SCAN_HARD_CAP)
                ->get();

            $encounterByVisit = $this->encounterIdsByVisit($rows->pluck('visit_id')->filter()->unique()->all());

            $problemCodes = ProblemList::where('patient_id', $patient->id)
                ->whereNotNull('icd_code')
                ->pluck('icd_code')
                ->map(fn ($c) => strtoupper((string) $c))
                ->flip();

            $mapped = $rows->map(fn (Diagnosis $d) => [
                'id'                  => $d->id,
                'encounter_id'        => $d->visit_id ? ($encounterByVisit[$d->visit_id] ?? null) : null,
                'patient_id'          => $d->patient_id,
                'icd10_code'          => $d->code,
                'icd10_description'   => $d->display_name,
                'diagnosis_type'      => $d->is_primary ? 'primary' : 'secondary',
                'diagnosis_nature'    => null,
                'sensitivity_level'   => null,
                'add_to_problem_list' => $d->code !== null && $problemCodes->has(strtoupper((string) $d->code)),
                'notes'               => null,
                'diagnosed_by'        => $d->provider_id,
                'created_at'          => $this->iso($d->created_at),
                'updated_at'          => $this->iso($d->updated_at),
            ])->values()->all();

            return $this->scanResult($mapped, count($mapped));
        });
    }

    /**
     * GET /api/mobile/doctor/patients/{id}/orders?scan=&type=lab|imaging
     *
     * Laboratory and imaging requests, newest first. `result_summary` is the
     * releasing bench's own conclusion where one exists — the radiologist's
     * impression — and null for laboratory orders, which release analyte rows
     * rather than a narrative. It is never a sentence written here.
     */
    public function orders(Request $request, string $id): JsonResponse
    {
        $type = $request->query('type');
        $type = is_string($type) && in_array($type, ['lab', 'imaging'], true) ? $type : null;

        return $this->chart(
            $request,
            $id,
            $type ? 'orders_' . $type : 'orders',
            'ClinicalOrder',
            function (Patient $patient) use ($type) {
                $rows = [];

                if ($type === null || $type === 'lab') {
                    $labs = LabOrder::where('patient_id', $patient->id)
                        ->orderByDesc('ordered_at')
                        ->limit(self::SCAN_HARD_CAP)
                        ->get();

                    $encounterByVisit = $this->encounterIdsByVisit($labs->pluck('visit_id')->filter()->unique()->all());

                    foreach ($labs as $o) {
                        $rows[] = [
                            'id'             => $o->id,
                            'encounter_id'   => $o->visit_id ? ($encounterByVisit[$o->visit_id] ?? null) : null,
                            'patient_id'     => $o->patient_id,
                            'order_type'     => 'lab',
                            'code'           => $o->test_code,
                            'name'           => $o->test_name,
                            'priority'       => $o->urgency,
                            'clinical_notes' => $o->clinical_indication ?: $o->notes,
                            'status'         => $o->status,
                            // A laboratory releases values, not a narrative.
                            'result_summary' => null,
                            'ordered_by'     => $o->ordered_by,
                            'ordered_at'     => $this->iso($o->ordered_at),
                            'created_at'     => $this->iso($o->created_at),
                            '_sort'          => $this->iso($o->ordered_at) ?? $this->iso($o->created_at) ?? '',
                        ];
                    }
                }

                if ($type === null || $type === 'imaging') {
                    $imaging = ImagingOrder::where('patient_id', $patient->id)
                        ->orderByDesc('ordered_at')
                        ->limit(self::SCAN_HARD_CAP)
                        ->get();

                    $encounterByVisit = $this->encounterIdsByVisit($imaging->pluck('visit_id')->filter()->unique()->all());

                    $impressions = RadiologyReport::whereIn('imaging_order_id', $imaging->pluck('id')->all())
                        ->pluck('impression', 'imaging_order_id');

                    foreach ($imaging as $o) {
                        $rows[] = [
                            'id'             => $o->id,
                            'encounter_id'   => $o->visit_id ? ($encounterByVisit[$o->visit_id] ?? null) : null,
                            'patient_id'     => $o->patient_id,
                            'order_type'     => 'imaging',
                            'code'           => $o->modality,
                            'name'           => trim($o->modality . ' ' . (string) $o->body_part),
                            'priority'       => $o->urgency,
                            'clinical_notes' => $o->clinical_indication ?: $o->notes,
                            'status'         => $o->status,
                            // The radiologist's own impression, verbatim.
                            'result_summary' => $impressions[$o->id] ?? null,
                            'ordered_by'     => $o->ordered_by,
                            'ordered_at'     => $this->iso($o->ordered_at),
                            'created_at'     => $this->iso($o->created_at),
                            '_sort'          => $this->iso($o->ordered_at) ?? $this->iso($o->created_at) ?? '',
                        ];
                    }
                }

                usort($rows, fn (array $a, array $b) => strcmp((string) $b['_sort'], (string) $a['_sort']));
                $rows = array_map(function (array $r) {
                    unset($r['_sort']);

                    return $r;
                }, $rows);

                return $this->scanResult($rows, count($rows));
            }
        );
    }

    // ══════════════════════════════════════════════════════════════════
    // The gate every endpoint above goes through
    // ══════════════════════════════════════════════════════════════════

    /**
     * Resolve the patient, prove the relationship, record the disclosure, then
     * — and only then — build the payload.
     *
     * Ordering is the whole point: the access check runs BEFORE a single
     * clinical row is read, and the audit row is committed BEFORE the payload
     * leaves. A response that reached the wire without a matching access_logs
     * row is not possible from this controller.
     *
     * @param  callable(Patient):mixed  $build
     * @param  bool  $envelopeFromBuilder  the builder returns ['payload'=>…, 'meta'=>…] itself
     */
    private function chart(
        Request $request,
        string $id,
        string $dataCategory,
        string $resourceType,
        callable $build,
        bool $envelopeFromBuilder = false
    ): JsonResponse {
        $this->applyRequestLocale($request);

        $clinicianId = $this->clinicianId($request);
        $facilityId  = $this->facilityId($request);

        $patient = $this->resolvePatient($id);

        if (! $patient) {
            // Same answer for "no such patient" and "a patient this demo
            // deployment isolates away". Nothing here confirms existence.
            return response()->json(['message' => __('doctor_chart.patient_not_found')], 404);
        }

        $basis = $this->relationshipBasis($patient, $clinicianId, $facilityId);

        if ($basis === null) {
            $this->auditDenial($patient, $clinicianId, $facilityId, $dataCategory, $resourceType);

            return response()->json([
                'message'         => __('doctor_chart.no_clinical_relationship'),
                'error_code'      => 'NO_CLINICAL_RELATIONSHIP',
                'required_action' => 'request_consent',
            ], 403);
        }

        if (! $this->auditRead($patient, $clinicianId, $facilityId, $basis, $dataCategory, $resourceType)) {
            // The trail is not optional. No trail, no disclosure.
            return response()->json(['message' => __('doctor_chart.audit_unavailable')], 503);
        }

        $built = $build($patient);

        if ($envelopeFromBuilder && is_array($built) && array_key_exists('payload', $built)) {
            return response()->json([
                'data' => $built['payload'],
                'meta' => $built['meta'] ?? new \stdClass(),
            ], 200);
        }

        return response()->json(['data' => $built], 200);
    }

    /**
     * ONE patient by EXACT id or EXACT Health ID. Nothing else resolves.
     *
     * Both forms are exact equality on a whole identifier — there is no LIKE,
     * no prefix and no name anywhere in this method, so it cannot be walked.
     * Two forms are accepted because the chart is reached both from the
     * Health-ID lookup (which answers with the record's uuid) and from an
     * appointment row (which carries the Health ID).
     *
     * Patient carries the IsDemoRecord global scope, deliberately not
     * bypassed: on a demo deployment a real patient reading as "not found" is
     * the isolation working.
     */
    private function resolvePatient(string $id): ?Patient
    {
        $key = trim($id);

        if ($key === '' || strlen($key) > 64) {
            return null;
        }

        if (Str::isUuid($key)) {
            // Follows a merge: a stale link may carry a retired record's id.
            return Patient::resolveId($key);
        }

        // Case-folding normalises one whole identifier; the comparison stays
        // an equality on the full Health ID. Follows a merge: a retired Health
        // ID still opens the surviving record.
        return Patient::resolveHealthId($key);
    }

    /**
     * The basis on which this clinician may read this chart, or null.
     *
     * Returns the FIRST basis that holds, cheapest and most specific first.
     * The returned string goes into the audit row's purpose, so the log says
     * on what authority the record was opened, not merely that it was.
     */
    private function relationshipBasis(Patient $patient, ?string $clinicianId, ?string $facilityId): ?string
    {
        if (! $clinicianId) {
            return null;
        }

        // 1. On this clinician's own schedule.
        if (Appointment::where('patient_id', $patient->id)->where('provider_id', $clinicianId)->exists()) {
            return 'appointment';
        }

        // 2. A current member of this patient's care team.
        if (
            CareTeamMember::where('patient_id', $patient->id)
                ->where('provider_id', $clinicianId)
                ->where('is_active', true)
                ->whereNull('left_at')
                ->exists()
        ) {
            return 'care_team';
        }

        // 3. This clinician personally did something clinical for this patient.
        $ownAct = Encounter::where('patient_id', $patient->id)->where('attending_provider_id', $clinicianId)->exists()
            || DB::table('visits')->where('patient_id', $patient->id)->where('provider_id', $clinicianId)->exists()
            || Prescription::where('patient_id', $patient->id)->where('prescribed_by', $clinicianId)->exists()
            || LabOrder::where('patient_id', $patient->id)->where('ordered_by', $clinicianId)->exists()
            || ImagingOrder::where('patient_id', $patient->id)->where('ordered_by', $clinicianId)->exists();

        if ($ownAct) {
            return 'treating_clinician';
        }

        // 4. An episode of care at the clinician's OWN facility — the token's
        //    facility, which no caller can name — that is open, still ahead,
        //    or recent enough to still be care rather than history.
        if ($facilityId && $this->hasFacilityEpisode($patient->id, $facilityId)) {
            return 'facility_episode';
        }

        // 5. The patient has granted this facility access outright.
        if ($facilityId && $this->hasActiveConsentGrant($patient->id, $facilityId)) {
            return 'consent_grant';
        }

        return null;
    }

    /**
     * Is there a live episode of care for this patient at this facility?
     *
     * "Live" is: still open, scheduled in the future, or begun within
     * FACILITY_EPISODE_LOOKBACK_DAYS. A ten-year-old closed visit is history,
     * and history is not a reason to open a chart today.
     */
    private function hasFacilityEpisode(string $patientId, string $facilityId): bool
    {
        $since = Carbon::now()->subDays(self::FACILITY_EPISODE_LOOKBACK_DAYS);

        $visit = DB::table('visits')
            ->where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('started_at', '>=', $since))
            ->exists();

        if ($visit) {
            return true;
        }

        $encounter = Encounter::where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('started_at', '>=', $since))
            ->exists();

        if ($encounter) {
            return true;
        }

        $appointment = Appointment::where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where('scheduled_at', '>=', $since)
            ->exists();

        if ($appointment) {
            return true;
        }

        return Prescription::where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where('prescribed_at', '>=', $since)
            ->exists();
    }

    /** An active, unexpired consent grant for this patient scoped to this facility. */
    private function hasActiveConsentGrant(string $patientId, ?string $facilityId): bool
    {
        if (! $facilityId) {
            return false;
        }

        return ConsentGrant::where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->where('status', 'active')
            ->where('expires_at', '>=', Carbon::now())
            ->exists();
    }

    // ══════════════════════════════════════════════════════════════════
    // Audit
    // ══════════════════════════════════════════════════════════════════

    /**
     * Record the disclosure. Returns false if it could not be recorded, which
     * the caller turns into a 503 rather than serving the record anyway.
     *
     * `purpose` carries both halves of the answer a reviewer needs:
     * `treatment` (why) and the relationship basis (on what authority).
     */
    private function auditRead(
        Patient $patient,
        ?string $clinicianId,
        ?string $facilityId,
        string $basis,
        string $dataCategory,
        string $resourceType
    ): bool {
        if (! $clinicianId) {
            return false;
        }

        try {
            AccessLogService::log(
                patientId: $patient->id,
                actorId: $clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: 'treatment:' . $basis,
                dataCategory: $dataCategory,
                resourceType: $resourceType,
                resourceId: null,
                accessType: 'read',
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Clinician chart read refused: access log could not be written.', [
                'patient_id'    => $patient->id,
                'clinician_id'  => $clinicianId,
                'data_category' => $dataCategory,
                'error'         => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Record a refusal. Best-effort on purpose: a refusal disclosed nothing,
     * so a failure to log one must not turn a correct 403 into a 500.
     */
    private function auditDenial(
        Patient $patient,
        ?string $clinicianId,
        ?string $facilityId,
        string $dataCategory,
        string $resourceType
    ): void {
        if (! $clinicianId) {
            return;
        }

        try {
            AccessLogService::log(
                patientId: $patient->id,
                actorId: $clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: 'treatment:no_relationship',
                dataCategory: $dataCategory,
                resourceType: $resourceType,
                resourceId: null,
                accessType: 'denied',
            );
        } catch (\Throwable $e) {
            Log::warning('Clinician chart denial was not access-logged.', [
                'patient_id'   => $patient->id,
                'clinician_id' => $clinicianId,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // Internals
    // ══════════════════════════════════════════════════════════════════

    /** The clinician's user id — stamped by AuthenticateMobileProvider. */
    private function clinicianId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_user_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * The facility this session speaks for. Platform rule: it comes only from
     * the request attributes, which the auth middleware set from the token —
     * never a header, body, query string or session.
     */
    private function facilityId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_facility_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * Honour `Accept-Language` for this response.
     *
     * Every message this controller emits goes through `__('doctor_chart.*')`,
     * and the mobile API has no locale negotiation of its own, so without this
     * a French-speaking clinician would be refused in English. Scoped to this
     * request; nothing global is touched.
     */
    private function applyRequestLocale(Request $request): void
    {
        $header = (string) $request->header('Accept-Language', '');

        if ($header !== '' && str_starts_with(strtolower(ltrim($header)), 'fr')) {
            App::setLocale('fr');
        }
    }

    /**
     * Wrap a list in the client's `ScanResult` envelope.
     *
     * The contract's scan vocabulary describes the OPES server, which builds
     * these lists by opening recent encounters one at a time — hence `scanned`,
     * `failed` and `truncated`. This server has patient-scoped tables and reads
     * them directly, so there is no fan-out to fail: `failed` is always 0 and
     * `truncated` is true only when SCAN_HARD_CAP genuinely cut the list short.
     * Reporting a partial read that did not happen would be as dishonest as
     * hiding one that did.
     *
     * @param  array<int,array<string,mixed>>  $items
     */
    private function scanResult(array $items, int $sourceRows): array
    {
        return [
            'items'     => array_values($items),
            'scanned'   => $sourceRows,
            'failed'    => 0,
            'truncated' => $sourceRows >= self::SCAN_HARD_CAP,
        ];
    }

    /**
     * The newest clinical note per visit.
     *
     * A note that has been amended is superseded by the amendment, and the
     * amendment is the newer row — clinical events are never overwritten here,
     * so "newest per visit" is the current text.
     *
     * @param  array<int,string>  $visitIds
     * @return array<string,ClinicalNote>
     */
    private function latestNotesByVisit(array $visitIds): array
    {
        if ($visitIds === []) {
            return [];
        }

        $notes = ClinicalNote::whereIn('visit_id', array_values(array_unique($visitIds)))
            ->orderBy('created_at')
            ->get();

        $byVisit = [];
        foreach ($notes as $note) {
            $byVisit[$note->visit_id] = $note;
        }

        return $byVisit;
    }

    /**
     * visit id → encounter id, for the contract's `encounter_id` field.
     *
     * The Finder hangs prescriptions, diagnoses and orders off a VISIT; the
     * clinician contract keys them to an ENCOUNTER. Where the visit produced
     * exactly one encounter the link is unambiguous and is followed; where it
     * produced several the field stays null rather than picking one.
     *
     * @param  array<int,string>  $visitIds
     * @return array<string,string>
     */
    private function encounterIdsByVisit(array $visitIds): array
    {
        if ($visitIds === []) {
            return [];
        }

        $rows = Encounter::whereIn('visit_id', array_values(array_unique($visitIds)))
            ->get(['id', 'visit_id']);

        $byVisit = [];
        foreach ($rows as $row) {
            if (array_key_exists($row->visit_id, $byVisit)) {
                $byVisit[$row->visit_id] = null; // ambiguous — say nothing
                continue;
            }
            $byVisit[$row->visit_id] = $row->id;
        }

        return array_filter($byVisit, fn ($v) => $v !== null);
    }

    /**
     * @param  array<int,string>  $userIds
     * @return array<string,string>
     */
    private function userNames(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return User::whereIn('id', array_values(array_unique($userIds)))
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Facility id → display name.
     *
     * `encounters.facility_id` points at the tenant facility register on some
     * rows and at the public care-facility directory on others, so both are
     * consulted and a name that is in neither stays null.
     *
     * @param  array<int,string>  $facilityIds
     * @return array<string,string>
     */
    private function facilityNames(array $facilityIds): array
    {
        if ($facilityIds === []) {
            return [];
        }

        $ids = array_values(array_unique($facilityIds));

        $names = DB::table('facilities')->whereIn('id', $ids)->pluck('name', 'id')->all();

        $missing = array_values(array_diff($ids, array_keys($names)));

        if ($missing !== []) {
            $names += DB::table('care_facilities')
                ->whereIn('id', $missing)
                ->pluck('facility_name', 'id')
                ->all();
        }

        return $names;
    }

    /**
     * The chart header.
     *
     * Contact details are included because a treating clinician reaching the
     * patient is the point of the header, and this whole read is audited. What
     * is NOT here is any clinical content: each section is its own request,
     * its own data category and its own audit row, so the log can say which
     * part of the record was actually opened.
     *
     * @return array<string,mixed>
     */
    private function formatPatient(Patient $patient, bool $consentGranted): array
    {
        $dob = $patient->date_of_birth;

        return [
            // The contract keys the chart on the Health ID; every endpoint here
            // also accepts the record's uuid, so both routes reach the chart.
            'id'              => $patient->health_id,
            'health_id'       => $patient->health_id,
            // The Finder keeps no facility MRN.
            'medical_id'      => null,
            'name'            => trim($patient->first_name . ' ' . $patient->last_name),
            'date_of_birth'   => $dob?->toDateString(),
            'age'             => $dob ? $dob->age : null,
            'sex'             => $patient->sex,
            'blood_group'     => $patient->blood_group,
            'phone'           => $patient->phone_number,
            'email'           => $patient->email,
            'address'         => $patient->address,
            // No city column on this deployment; the address carries it.
            'city'            => null,
            'next_of_kin'     => $this->nextOfKin($patient),
            'has_allergies'   => AllergyRecord::where('patient_id', $patient->id)->exists(),
            'consent_granted' => $consentGranted,
            'created_at'      => $this->iso($patient->created_at),
            'updated_at'      => $this->iso($patient->updated_at),
        ];
    }

    /**
     * `patients.emergency_contact` is free-form json across the estate, so the
     * three fields the chart wants are read under several spellings and left
     * null when the record simply does not hold them.
     *
     * @return array{name:?string,phone:?string,relation:?string}
     */
    private function nextOfKin(Patient $patient): array
    {
        $raw = $patient->emergency_contact;

        if (! is_array($raw)) {
            return ['name' => null, 'phone' => null, 'relation' => null];
        }

        $pick = function (array $keys) use ($raw): ?string {
            foreach ($keys as $key) {
                if (isset($raw[$key]) && is_scalar($raw[$key]) && (string) $raw[$key] !== '') {
                    return (string) $raw[$key];
                }
            }

            return null;
        };

        return [
            'name'     => $pick(['name', 'full_name', 'contact_name']),
            'phone'    => $pick(['phone', 'phone_number', 'contact_phone', 'telephone']),
            'relation' => $pick(['relation', 'relationship', 'relation_to_patient']),
        ];
    }

    /** A query-string integer, clamped. Never trusted raw. */
    private function boundedInt(mixed $value, int $default, int $min, int $max): int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }

    /** ISO-8601, or null. Never a formatted "now" standing in for a missing time. */
    private function iso(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toIso8601String();
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->toIso8601String();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
