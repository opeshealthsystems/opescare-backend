<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\ClinicalNote;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\TriageRecord;
use App\Models\Visit;
use App\Models\VitalSign;
use App\Modules\EncounterManagement\Services\ConsultationService;
use App\Modules\OperationalFlow\Services\VisitManagementService;
use App\Modules\Triage\Services\TriageService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * VisitPortalController — the front-desk-to-consultation flow for ONE facility.
 *
 * This controller was the widest hole of the set. `index()` listed every visit
 * in the country and handed the staff member a picker containing the first 200
 * patients in the patients table, and every action below it took a visit id
 * straight off the URL into `Visit::findOrFail()` — so triage, consultation
 * notes, cancellation and status transitions could all be performed on another
 * facility's encounter simply by pasting its id.
 *
 * So: the facility comes from the session, never from `Facility::value('id')`;
 * every visit is fetched through `visitAtFacility()`, which 404s a visit that
 * is not ours; and the patient picker is limited to people this facility has
 * actually registered or seen. Reads and writes of the clinical record are
 * audited through PortalContextService.
 *
 * The free-text patient_id field the view falls back to is deliberately left
 * open: a national Health ID is portable, and a patient who has never been here
 * before must still be admissible. What is closed is the *listing* of patients
 * this facility has no relationship with.
 */
class VisitPortalController extends Controller
{
    public function __construct(private readonly PortalContextService $context) {}

    // -----------------------------------------------------------------
    // Context helpers
    // -----------------------------------------------------------------

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-staff';
    }

    /**
     * The facility this request acts for — session-resolved, fails closed.
     *
     * The single-facility fallback holds only when there is exactly one
     * facility. With 345 and no resolved context there is no safe guess, so
     * this 409s rather than opening someone else's waiting room.
     */
    private function facilityId(): string
    {
        $resolved = $this->context->facilityId();

        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        abort_unless(
            Facility::count() === 1,
            409,
            'No facility is selected for this session, so there is no way to tell '
            . 'whose visit this is. Select a facility first.'
        );

        return (string) Facility::value('id');
    }

    /** A malformed id is a 404, not a Postgres 22P02 cast error surfacing as a 500. */
    private function assertUuid(string $id): string
    {
        abort_unless(Str::isUuid($id), 404);

        return $id;
    }

    /** A visit, only if it belongs to the acting facility. */
    private function visitAtFacility(string $id, array $with = []): Visit
    {
        return Visit::with($with)
            ->where('id', $this->assertUuid($id))
            ->where('facility_id', $this->facilityId())
            ->firstOrFail();
    }

    // -----------------------------------------------------------------
    // Visits list
    // -----------------------------------------------------------------

    public function index(Request $req)
    {
        $facilityId = $this->facilityId();

        $q = Visit::with(['patient'])
            ->where('facility_id', $facilityId)
            ->orderByDesc('started_at');

        if ($status = $req->input('status')) {
            $q->where('status', $status);
        }

        if ($patientId = $req->input('patient_id')) {
            $q->where('patient_id', $patientId);
        }

        // Default: exclude completed/cancelled from list unless filtered
        if (!$req->input('status')) {
            $q->whereNotIn('status', ['completed', 'cancelled', 'abandoned']);
        }

        $visits = $q->limit(100)->get();

        // The picker used to be `Patient::limit(200)` — the national patient
        // list, name and health ID, shown to every front desk in the country.
        // It is now the people this facility registered or has already seen.
        $patients = Patient::query()
            ->where(function ($sub) use ($facilityId) {
                $sub->where('facility_id', $facilityId)
                    ->orWhereIn('id', Visit::where('facility_id', $facilityId)->select('patient_id'));
            })
            ->limit(200)
            ->get();

        $this->context->auditPatientAccess(
            actionType:   'visit_list_view',
            resourceType: 'Visit',
        );

        return view('portals.staff.visits.index', compact('visits', 'patients'));
    }

    // -----------------------------------------------------------------
    // Create visit
    // -----------------------------------------------------------------

    public function store(Request $req, VisitManagementService $svc)
    {
        $data = $req->validate([
            'patient_id' => 'required|string',
            'visit_type' => 'required|in:general,followup,specialist,emergency,lab,pharmacy',
        ]);

        try {
            $visit = $svc->createVisit(array_merge($data, [
                'facility_id' => $this->facilityId(),
                'provider_id' => null,
            ]));

            $this->context->auditPatientAccess(
                actionType:   'visit_created',
                resourceType: 'Visit',
                resourceId:   $visit->id,
                patientId:    $visit->patient_id,
            );

            return redirect()->route('portals.staff.visits')
                ->with('success', __('flash.visit_created', ['id' => substr($visit->id, 0, 8)]));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.visit_create_failed', ['error' => $e->getMessage()]));
        }
    }

    // -----------------------------------------------------------------
    // Status transitions
    // -----------------------------------------------------------------

    public function transition(string $id, Request $req, VisitManagementService $svc)
    {
        $data = $req->validate([
            'status' => 'required|string',
        ]);

        $visit = $this->visitAtFacility($id);

        try {
            $svc->transition($visit->id, $data['status'], $this->demoActorId());

            return back()->with('success', __('flash.visit_status_updated', ['status' => ucwords(str_replace('_', ' ', $data['status']))]));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.visit_advance_failed', ['error' => $e->getMessage()]));
        }
    }

    public function complete(string $id, VisitManagementService $svc)
    {
        $visit = $this->visitAtFacility($id);

        try {
            $svc->complete($visit->id, $this->demoActorId());

            return back()->with('success', __('flash.visit_completed'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.visit_complete_failed', ['error' => $e->getMessage()]));
        }
    }

    public function cancel(string $id, VisitManagementService $svc)
    {
        $visit = $this->visitAtFacility($id);

        try {
            $svc->cancel($visit->id, $this->demoActorId());

            return back()->with('success', __('flash.visit_cancelled'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.visit_cancel_failed', ['error' => $e->getMessage()]));
        }
    }

    // -----------------------------------------------------------------
    // Triage
    // -----------------------------------------------------------------

    public function triage(string $id)
    {
        $visit = $this->visitAtFacility($id, ['patient', 'triageRecords.vitalSigns']);

        $this->context->auditPatientAccess(
            actionType:   'visit_triage_view',
            resourceType: 'Visit',
            resourceId:   $visit->id,
            patientId:    $visit->patient_id,
        );

        return view('portals.staff.visits.triage', compact('visit'));
    }

    public function triageEscalate(string $id, Request $req, TriageService $svc)
    {
        $req->validate(['reason' => 'required|string|max:500']);

        $visit = $this->visitAtFacility($id);

        try {
            $svc->escalateEmergency($visit->id, $req->reason, $this->demoActorId());

            $this->context->auditPatientAccess(
                actionType:   'visit_triage_escalated',
                resourceType: 'Visit',
                resourceId:   $visit->id,
                patientId:    $visit->patient_id,
            );

            return redirect()->route('portals.staff.visits.triage', $visit->id)
                ->with('success', __('flash.visit_escalated_emergency'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.visit_escalation_failed', ['error' => $e->getMessage()]));
        }
    }

    public function triageStore(string $id, Request $req, TriageService $svc)
    {
        $data = $req->validate([
            'presenting_complaint' => 'required|string|max:1000',
            'pain_score'           => 'nullable|integer|min:0|max:10',
            'acuity_score'         => 'required|in:critical,urgent,semi_urgent,non_urgent,resuscitation',
            'pregnancy_status'     => 'nullable|string|max:50',
            // vitals
            'temperature'              => 'nullable|numeric|min:20|max:45',
            'blood_pressure_systolic'  => 'nullable|integer|min:40|max:300',
            'blood_pressure_diastolic' => 'nullable|integer|min:20|max:200',
            'pulse'                    => 'nullable|integer|min:20|max:300',
            'respiratory_rate'         => 'nullable|integer|min:4|max:60',
            'oxygen_saturation'        => 'nullable|numeric|min:50|max:100',
            'weight'                   => 'nullable|numeric|min:0.5|max:500',
            'height'                   => 'nullable|numeric|min:20|max:250',
        ]);

        $visit = $this->visitAtFacility($id);

        try {
            $vitals = array_filter([
                'temperature'              => $data['temperature'] ?? null,
                'blood_pressure_systolic'  => $data['blood_pressure_systolic'] ?? null,
                'blood_pressure_diastolic' => $data['blood_pressure_diastolic'] ?? null,
                'pulse'                    => $data['pulse'] ?? null,
                'respiratory_rate'         => $data['respiratory_rate'] ?? null,
                'oxygen_saturation'        => $data['oxygen_saturation'] ?? null,
                'weight'                   => $data['weight'] ?? null,
                'height'                   => $data['height'] ?? null,
            ], fn($v) => $v !== null);

            $svc->recordTriage([
                'visit_id'             => $visit->id,
                'patient_id'           => $visit->patient_id,
                'facility_id'          => $visit->facility_id,
                'nurse_id'             => $this->demoActorId(),
                'presenting_complaint' => $data['presenting_complaint'],
                'pain_score'           => $data['pain_score'] ?? null,
                'acuity_score'         => $data['acuity_score'],
                'pregnancy_status'     => $data['pregnancy_status'] ?? null,
                'vitals'               => $vitals ?: null,
            ], $this->demoActorId());

            // Advance visit status
            if ($visit->status === 'open') {
                $visit->update(['status' => 'in_triage']);
            }

            $this->context->auditPatientAccess(
                actionType:   'visit_triage_recorded',
                resourceType: 'Visit',
                resourceId:   $visit->id,
                patientId:    $visit->patient_id,
            );

            return redirect()->route('portals.staff.visits')
                ->with('success', __('flash.visit_triage_recorded'));
        } catch (Throwable $e) {
            return back()->with('error', __('flash.visit_triage_failed', ['error' => $e->getMessage()]));
        }
    }

    // -----------------------------------------------------------------
    // Consultation
    // -----------------------------------------------------------------

    public function consult(string $id)
    {
        $visit = $this->visitAtFacility($id, ['patient', 'clinicalNotes', 'triageRecords.vitalSigns']);

        $this->context->auditPatientAccess(
            actionType:   'visit_consultation_view',
            resourceType: 'Visit',
            resourceId:   $visit->id,
            patientId:    $visit->patient_id,
        );

        return view('portals.staff.visits.consult', compact('visit'));
    }

    public function consultStore(string $id, Request $req, ConsultationService $svc, VisitManagementService $visitSvc)
    {
        $data = $req->validate([
            'history_of_present_illness' => 'required|string|min:10|max:5000',
            'examination_findings'       => 'nullable|string|max:5000',
            'treatment_plan'             => 'nullable|string|max:5000',
            'status'                     => 'required|in:draft,signed',
        ]);

        $visit = $this->visitAtFacility($id);

        try {
            $svc->saveClinicalNote(array_merge($data, [
                'visit_id'    => $visit->id,
                'provider_id' => $this->demoActorId(),
            ]), $this->demoActorId());

            // Advance visit status to in_consultation if still earlier
            if (in_array($visit->status, ['open', 'in_triage', 'in_queue'])) {
                $visit->update(['status' => 'in_consultation']);
            }

            $this->context->auditPatientAccess(
                actionType:   'visit_clinical_note_saved',
                resourceType: 'Visit',
                resourceId:   $visit->id,
                patientId:    $visit->patient_id,
            );

            $msg = $data['status'] === 'signed' ? 'Clinical note signed.' : 'Clinical note saved as draft.';

            return redirect()->route('portals.staff.visits')
                ->with('success', $msg);
        } catch (Throwable $e) {
            return back()->with('error', __('flash.visit_note_save_failed', ['error' => $e->getMessage()]));
        }
    }
}
