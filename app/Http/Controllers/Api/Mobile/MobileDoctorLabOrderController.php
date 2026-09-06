<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabOrderResource;
use App\Models\Appointment;
use App\Models\CareTeamMember;
use App\Models\ConsentGrant;
use App\Models\Encounter;
use App\Models\ImagingOrder;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Prescription;
use App\Modules\Governance\Services\AccessLogService;
use App\Support\PatientPush;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
 *   a care relationship         relationshipBasis(), below — the chart's five
 *                               bases, copied verbatim. Holding a patient's id
 *                               is not authorisation to order a test against
 *                               their record, any more than it is to read it.
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
    /** The chart's figure for how long an episode at the own facility stays "care". */
    private const FACILITY_EPISODE_LOOKBACK_DAYS = 365;

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

        $basis = $this->relationshipBasis($record, $clinicianId, $facilityId);

        if ($basis === null) {
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
        $this->auditWrite($record, $order, $clinicianId, $facilityId, $basis);

        $this->notifyPatient($record, $order, $facilityId);

        return response()->json(['data' => LabOrderResource::make($order)], 201);
    }

    // ══════════════════════════════════════════════════════════════════
    // The care-relationship gate — MobileDoctorChartController's rule, copied
    // ══════════════════════════════════════════════════════════════════

    /**
     * ONE patient by EXACT id or EXACT Health ID. The chart's resolvePatient(),
     * unchanged: equality on a whole identifier, following a merge, scoped by
     * IsDemoRecord and deliberately not bypassed.
     */
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

    /**
     * The basis on which this clinician may order against this record, or null.
     *
     * MobileDoctorChartController::relationshipBasis(), verbatim — same five
     * bases, same order, same vocabulary, and the returned string goes into the
     * audit row's purpose. That method is private and its file is not this
     * one's to edit, so the rule is copied rather than called.
     */
    private function relationshipBasis(Patient $patient, string $clinicianId, ?string $facilityId): ?string
    {
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

    /** Is there a live episode of care for this patient at this facility? The chart's rule, unchanged. */
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
    // Audit + the patient's notice
    // ══════════════════════════════════════════════════════════════════

    /** Record the write, with the authority it was placed on. Never fatal. */
    private function auditWrite(Patient $patient, LabOrder $order, string $clinicianId, string $facilityId, string $basis): void
    {
        try {
            AccessLogService::log(
                patientId: $patient->id,
                actorId: $clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: 'treatment:' . $basis,
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
                purpose: 'treatment:no_relationship',
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
