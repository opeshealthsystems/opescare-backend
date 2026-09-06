<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabOrderResource;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Modules\Governance\Services\AccessLogService;
use App\Support\ClinicalRelationshipGate;
use App\Support\PatientPush;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Mobile Provider API — ordering a laboratory test from the phone.
 *
 * POST /api/mobile/doctor/patients/{patient}/lab-orders
 *
 * The chart (MobileDoctorChartController) is a READ surface and stays one;
 * this is the one write that hangs off its URL space, in its own file so the
 * two never blur. It lives behind the same two gates:
 *
 *   capability:clinical_chart   the route — doctors, nurses, trainees and
 *                               medical supervisors. Front desk, pharmacy, lab
 *                               and billing get 403 {error_code: role_forbidden}
 *                               before this file runs.
 *
 *   a care relationship         App\Support\ClinicalRelationshipGate — the
 *                               chart's five bases, the one shared rule.
 *                               Holding a patient's id is not authorisation to
 *                               order a test against their record, any more
 *                               than it is to read it.
 *
 * WHO ORDERS, WHERE. `ordered_by` is the clinician the bearer token names and
 * `facility_id` is the facility the token names — both read from
 * `$request->attributes` (stamped by AuthenticateMobileProvider) and from
 * nowhere else. There is no parameter in the body that could set either. The
 * order is `status: ordered`, so the facility's lab desk sees it as new work.
 *
 * WHAT IS WRITTEN. One `lab_orders` row, immutable from the moment it is
 * placed (see LabOrder: test_name / test_code / patient / facility / ordering
 * clinician can never be changed after the fact). `is_demo` follows the
 * patient, so a demo clinician's order can never attach to a real record or
 * the reverse. Then the patient is told (bell + push) that a test has been
 * ordered for them and by which facility — the indication itself stays off the
 * lock screen.
 *
 * {patient} is an EXACT `patients.id` or an EXACT Health ID, matched by
 * equality on the whole identifier (the chart's resolvePatient() rule): no
 * prefix, no LIKE, no name, no listing.
 */
class MobileDoctorLabOrderController extends Controller
{
    /** The vocabulary `lab_orders.urgency` was created with. */
    private const URGENCIES = ['routine', 'urgent', 'stat'];

    /** The bell/push `type` the patient app switches on for a new order. */
    private const PATIENT_ORDERED_TYPE = 'lab_test_ordered';

    /**
     * POST /api/mobile/doctor/patients/{patient}/lab-orders
     *
     * Body: { test_name ≤160, test_code? ≤40, urgency: routine|urgent|stat,
     *         clinical_indication ≤1000 }
     *
     * 201 { data: LabOrderResource } — the row the chart's `orders` section
     * will list on its next read, in the same shape.
     */
    public function store(Request $request, string $patient): JsonResponse
    {
        $this->applyRequestLocale($request);

        $validated = $request->validate([
            'test_name'           => ['required', 'string', 'max:160'],
            'test_code'           => ['nullable', 'string', 'max:40'],
            'urgency'             => ['required', 'string', 'in:' . implode(',', self::URGENCIES)],
            'clinical_indication' => ['required', 'string', 'max:1000'],
        ]);

        $clinicianId = $this->clinicianId($request);
        $facilityId  = $this->facilityId($request);

        if ($clinicianId === null) {
            abort(401, 'Unauthenticated.');
        }

        if ($facilityId === null) {
            // An order has to be placed SOMEWHERE, and the only somewhere this
            // surface accepts is the token's facility.
            return response()->json(['message' => __('doctor_results.order_no_facility')], 422);
        }

        $record = $this->resolvePatient($patient);

        if (! $record) {
            // Same answer for "no such patient" and "a patient this demo
            // deployment isolates away". Nothing here confirms existence.
            return response()->json(['message' => __('doctor_results.patient_not_found')], 404);
        }

        $verdict = ClinicalRelationshipGate::evaluate($clinicianId, $facilityId, $record->id);

        if (! $verdict->holds()) {
            $this->auditDenial($record, $clinicianId, $facilityId);

            return response()->json([
                'message'         => __('doctor_results.no_clinical_relationship'),
                'error_code'      => 'NO_CLINICAL_RELATIONSHIP',
                'required_action' => 'request_consent',
            ], 403);
        }

        $testCode = trim((string) ($validated['test_code'] ?? ''));

        $order = new LabOrder([
            'patient_id'          => $record->id,
            'facility_id'         => $facilityId,
            'visit_id'            => null,
            'ordered_by'          => $clinicianId,
            'test_name'           => trim($validated['test_name']),
            'test_code'           => $testCode === '' ? null : $testCode,
            'urgency'             => $validated['urgency'],
            'status'              => 'ordered',
            'clinical_indication' => trim($validated['clinical_indication']),
            'notes'               => null,
            'ordered_at'          => now(),
        ]);

        // Not mass-assignable on LabOrder (that file is not this one's to
        // edit); set directly so the order follows the patient's partition.
        $order->is_demo = (bool) $record->is_demo;

        try {
            $order->save();
        } catch (\Throwable $e) {
            Log::error('doctor lab order: not placed', [
                'patient_id'   => $record->id,
                'clinician_id' => $clinicianId,
                'error'        => $e->getMessage(),
            ]);

            return response()->json(['message' => __('doctor_results.order_failed')], 422);
        }

        // The order row is itself the clinical trail (who, for whom, where,
        // when); the access log adds the AUTHORITY it was placed on. Best
        // effort after the commit — a committed order is not un-placed because
        // the audit table was unavailable, but the failure is logged loudly.
        $this->auditWrite($record, $order, $clinicianId, $facilityId, $verdict);

        $this->notifyPatient($record, $order, $facilityId);

        return response()->json(['data' => LabOrderResource::make($order)], 201);
    }

    // ══════════════════════════════════════════════════════════════════
    // Identity of the record — the chart's resolvePatient(), unchanged
    // ══════════════════════════════════════════════════════════════════

    /**
     * ONE patient by EXACT id or EXACT Health ID. The chart's resolvePatient(),
     * unchanged: equality on a whole identifier, following a merge, scoped by
     * IsDemoRecord and deliberately not bypassed.
     */
    /**
     * GET /api/mobile/doctor/lab-catalogue?q=
     *
     * The tests laboratories have published, one entry per code, for the
     * clinician picking what to order. Same source and shape as the patient's
     * request catalogue (MobileLabRequestController::catalogue) so both sides
     * name a test the same way; `category` stays null (the table records none).
     */
    public function catalogue(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $query = DB::table('lab_test_availability')
            ->whereNotIn(DB::raw('lower(availability_status)'), ['unavailable', 'discontinued', 'withdrawn']);

        if (mb_strlen($term) >= 2) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('test_name', 'ilike', $like)
                    ->orWhere('local_test_code', 'ilike', $like)
                    ->orWhere('loinc_code', 'ilike', $like);
            });
        }

        $rows = $query->orderBy('test_name')->limit(160)
            ->get(['test_name', 'local_test_code', 'loinc_code', 'specimen_type']);

        $catalogue = [];
        foreach ($rows as $row) {
            $code = trim((string) ($row->local_test_code ?: ($row->loinc_code ?: Str::upper(Str::slug((string) $row->test_name, '_')))));
            if ($code === '' || isset($catalogue[$code])) {
                continue;
            }
            $catalogue[$code] = [
                'code'       => $code,
                'name'       => (string) $row->test_name,
                'loinc_code' => $row->loinc_code ? (string) $row->loinc_code : null,
                'category'   => null,
                'specimen'   => $row->specimen_type ? (string) $row->specimen_type : null,
            ];
            if (count($catalogue) >= 40) {
                break;
            }
        }

        return response()->json(['data' => array_values($catalogue)]);
    }

    private function resolvePatient(string $id): ?Patient
    {
        $key = trim($id);

        if ($key === '' || strlen($key) > 64) {
            return null;
        }

        if (Str::isUuid($key)) {
            return Patient::resolveId($key);
        }

        return Patient::resolveHealthId($key);
    }

    // ══════════════════════════════════════════════════════════════════
    // Audit + the patient's notice
    // ══════════════════════════════════════════════════════════════════

    /** Record the write, with the authority it was placed on. Never fatal. */
    private function auditWrite(Patient $patient, LabOrder $order, string $clinicianId, string $facilityId, ClinicalRelationshipGate $verdict): void
    {
        try {
            AccessLogService::log(
                patientId: $patient->id,
                actorId: $clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: $verdict->purpose(),
                dataCategory: 'orders_lab',
                resourceType: 'LabOrder',
                resourceId: $order->id,
                accessType: 'write',
            );
        } catch (\Throwable $e) {
            Log::error('doctor lab order: access log failed', [
                'patient_id' => $patient->id,
                'order_id'   => $order->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /** Record a refusal. Best-effort: a refusal disclosed nothing. */
    private function auditDenial(Patient $patient, string $clinicianId, ?string $facilityId): void
    {
        try {
            AccessLogService::log(
                patientId: $patient->id,
                actorId: $clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: ClinicalRelationshipGate::NO_RELATIONSHIP_PURPOSE,
                dataCategory: 'orders_lab',
                resourceType: 'LabOrder',
                resourceId: null,
                accessType: 'denied',
            );
        } catch (\Throwable $e) {
            Log::warning('doctor lab order: denial was not access-logged', [
                'patient_id'   => $patient->id,
                'clinician_id' => $clinicianId,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    /**
     * Tell the patient a test has been ordered for them — bell row plus push,
     * in THEIR language. Names the test and the facility; the clinical
     * indication stays off the lock screen. Never fatal: the order is placed.
     */
    private function notifyPatient(Patient $patient, LabOrder $order, string $facilityId): void
    {
        try {
            $locale = class_exists(PatientPush::class) ? PatientPush::localeFor($patient) : App::getLocale();

            $facilityName = (string) (DB::table('facilities')->where('id', $facilityId)->value('name') ?? '');

            $title = __('doctor_results.patient_notice.ordered_title', ['test' => $order->test_name], $locale);
            $body  = __('doctor_results.patient_notice.ordered_body', [
                'facility' => $facilityName !== '' ? $facilityName : __('doctor_results.patient_notice.your_facility', [], $locale),
                'test'     => $order->test_name,
            ], $locale);
            $actionUrl = '/labs/' . $order->id;

            DB::table('notifications')->insert([
                'id'              => (string) Str::uuid(),
                'type'            => self::PATIENT_ORDERED_TYPE,
                'notifiable_type' => $patient->getMorphClass(),
                'notifiable_id'   => $patient->getKey(),
                'data'            => json_encode([
                    'type'         => self::PATIENT_ORDERED_TYPE,
                    'title'        => $title,
                    'message'      => $body,
                    'severity'     => $order->urgency === 'stat' ? 'high' : 'normal',
                    'action_url'   => $actionUrl,
                    'action_label' => __('doctor_results.patient_notice.ordered_action', [], $locale),
                    'lab_order_id' => (string) $order->id,
                    'urgency'      => $order->urgency,
                ], JSON_UNESCAPED_UNICODE),
                'read_at'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            if (class_exists(PatientPush::class)) {
                PatientPush::send($patient, 'lab_result', $title, $body, [
                    'lab_order_id' => (string) $order->id,
                    'action_url'   => $actionUrl,
                    'urgency'      => $order->urgency,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('doctor lab order: patient notice not sent', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // Identity — the token, and only the token
    // ══════════════════════════════════════════════════════════════════

    /** The clinician's user id — stamped by AuthenticateMobileProvider. */
    private function clinicianId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_user_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * The facility this session speaks for. Platform rule: request attributes
     * only, set by the auth middleware from the token — never a header, body,
     * query string or session.
     */
    private function facilityId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_facility_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /** Honour `Accept-Language` / `?lang=` for this response, as the chart and the inbox do. */
    private function applyRequestLocale(Request $request): void
    {
        $requested = $request->query('lang');

        if (! is_string($requested) || ! in_array($requested, ['en', 'fr'], true)) {
            $requested = str_starts_with(strtolower(ltrim((string) $request->header('Accept-Language', ''))), 'fr')
                ? 'fr'
                : null;
        }

        if ($requested !== null) {
            App::setLocale($requested);
        }
    }
}
