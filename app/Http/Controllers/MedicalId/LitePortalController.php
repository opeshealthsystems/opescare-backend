<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\LiteConflict;
use App\Models\LiteDevice;
use App\Models\LiteOfflineEvent;
use App\Models\Patient;
use App\Modules\OpesCareLite\Services\OpesCareLiteService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * OpesCare Lite — Simplified portal for small/low-connectivity facilities.
 *
 * Provides a stripped-down, large-button interface covering essential
 * care workflows. The same backend rules apply; only the UI is lighter.
 */
class LitePortalController extends Controller
{
    public function __construct(
        private readonly OpesCareLiteService $liteService,
        private readonly PortalContextService $ctx,
    ) {}

    // ------------------------------------------------------------------
    // Request context
    // ------------------------------------------------------------------

    /**
     * The facility this Lite session is working in.
     *
     * Every screen below reads or writes patient data, so "which facility?"
     * has to be answered from the signed-in session. It used to be answered by
     * `Facility::value('id')` — whichever row Postgres returned first out of
     * 345 — which meant a Lite clinic registered its patients into, and read
     * its queue from, a hospital it had no relationship with.
     *
     * The single-facility fallback is honoured only when there genuinely is
     * exactly one facility, the condition that made it safe (the same shape as
     * InventoryPortalController::bloodFacilityId()). Otherwise this fails
     * closed rather than guessing.
     */
    private function facilityId(): string
    {
        $resolved = $this->ctx->facilityId();

        if ($resolved !== null && $resolved !== '') {
            return $resolved;
        }

        abort_unless(
            Facility::count() === 1,
            409,
            'No facility is selected for this session, so there is no way to tell '
            . 'which clinic this Lite screen belongs to. Select a facility first.'
        );

        return (string) Facility::value('id');
    }

    private function demoActorId(): string
    {
        return session('auth_email') ?: 'demo-staff';
    }

    /**
     * A patient this facility is allowed to work with, or null.
     *
     * The Lite screens take `patient_id` straight off the query string / form
     * body, so an unscoped `Patient::find()` here hands any patient in the
     * country to whoever guesses a UUID. Lookup already only surfaces this
     * facility's patients, so nothing reachable in the UI changes.
     */
    private function findFacilityPatient(?string $patientId, string $facilityId): ?Patient
    {
        if (! $patientId) {
            return null;
        }

        return Patient::where('facility_id', $facilityId)->find($patientId);
    }

    // ------------------------------------------------------------------
    // Portal views
    // ------------------------------------------------------------------

    /**
     * Simplified Lite dashboard — today's snapshot.
     */
    public function dashboard(): View
    {
        $facilityId = $this->facilityId();
        $stats      = $this->liteService->getAdminStats($facilityId);

        // Today's queue summary (reuse existing queue table)
        $todayQueue = \App\Models\QueueTicket::where('facility_id', $facilityId)
            ->whereDate('created_at', today())
            ->selectRaw("status, COUNT(*) as cnt")
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->all();

        // Recent patients seen today
        $recentPatients = Patient::where('facility_id', $facilityId)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get(['id', 'first_name', 'last_name', 'health_id', 'updated_at']);

        return view('portals.lite.dashboard', compact(
            'stats', 'todayQueue', 'recentPatients'
        ));
    }

    /**
     * Health ID lookup — search by Health ID, name, or phone.
     */
    public function lookup(Request $request): View
    {
        $facilityId = $this->facilityId();
        $query      = $request->input('q', '');
        $patients   = collect();

        if (strlen($query) >= 2) {
            $patients = Patient::where('facility_id', $facilityId)
                ->where(function ($q) use ($query) {
                    $q->where('health_id', 'like', "%{$query}%")
                      ->orWhere('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('phone', 'like', "%{$query}%");
                })
                ->orderBy('last_name')
                ->limit(20)
                ->get(['id', 'first_name', 'last_name', 'health_id', 'date_of_birth', 'phone']);
        }

        return view('portals.lite.lookup', compact('query', 'patients'));
    }

    /**
     * Basic patient registration form.
     */
    public function registerPatientForm(): View
    {
        // Resolved up front so the form is only shown where the resulting
        // registration can actually be filed.
        $this->facilityId();

        return view('portals.lite.register_patient');
    }

    /**
     * Store a new patient from Lite registration form.
     */
    public function registerPatientStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'date_of_birth' => 'nullable|date',
            'gender'        => 'nullable|in:male,female',
            'phone'         => 'nullable|string|max:30',
        ]);

        $facilityId = $this->facilityId();

        $patient = Patient::create([
            ...$data,
            'facility_id' => $facilityId,
            'health_id'   => 'HC-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'created_by'  => $this->demoActorId(),
        ]);

        $this->ctx->auditPatientAccess(
            actionType:   'lite_patient_registered',
            resourceType: 'Patient',
            resourceId:   $patient->id,
            patientId:    $patient->id,
        );

        return redirect()->route('portals.lite.lookup', ['q' => $patient->health_id])
            ->with('success', __('lite_chrome.flash_patient_registered', [
                'name'      => trim("{$patient->first_name} {$patient->last_name}"),
                'health_id' => $patient->health_id,
            ]));
    }

    /**
     * Quick check-in form — add patient to today's queue.
     */
    public function checkIn(Request $request): View
    {
        $facilityId = $this->facilityId();
        $patient    = $this->findFacilityPatient($request->query('patient_id'), $facilityId);

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'lite_checkin_view',
                resourceType: 'Patient',
                resourceId:   $patient->id,
                patientId:    $patient->id,
            );
        }

        return view('portals.lite.checkin', compact('patient'));
    }

    /**
     * Process check-in submission.
     */
    public function checkInStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|uuid|exists:patients,id',
            'reason'     => 'nullable|string|max:300',
            'priority'   => 'nullable|integer|min:1|max:5',
        ]);

        $facilityId = $this->facilityId();

        // `exists:patients,id` proves the patient is real, not that they are
        // ours. Without this check any patient UUID in the country could be
        // pulled into this facility's queue — which creates a visit and a
        // check-in record against their longitudinal file.
        $patient = $this->findFacilityPatient($data['patient_id'], $facilityId);
        abort_unless($patient, 404);

        // Walk-in check-in goes through the canonical QueueService, which creates
        // the PatientCheckIn + QueueTicket (with a generated queue number) and a
        // visit. destination_queue is a FacilityQueue name — use the facility's
        // first active queue. If none is configured, fail gracefully.
        $queue = \App\Models\FacilityQueue::where('facility_id', $facilityId)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->first();

        if (! $queue) {
            return redirect()->route('portals.lite.dashboard')
                ->with('error', __('flash.no_queue_configured'));
        }

        app(\App\Modules\Queue\Services\QueueService::class)->checkInWalkIn([
            'patient_id'        => $patient->id,
            'facility_id'       => $facilityId,
            'destination_queue' => $queue->name,
            'priority_level'    => $data['priority'] ?? 5,
            'check_in_type'     => 'walk_in',
            'actor_id'          => $this->demoActorId(),
        ]);

        $this->ctx->auditPatientAccess(
            actionType:   'lite_patient_checked_in',
            resourceType: 'Patient',
            resourceId:   $patient->id,
            patientId:    $patient->id,
        );

        return redirect()->route('portals.lite.dashboard')
            ->with('success', __('flash.patient_checked_in_queue'));
    }

    /**
     * Simple consultation note form.
     */
    public function consultation(Request $request): View
    {
        $patient = $this->findFacilityPatient($request->query('patient_id'), $this->facilityId());

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'lite_consultation_view',
                resourceType: 'Patient',
                resourceId:   $patient->id,
                patientId:    $patient->id,
            );
        }

        return view('portals.lite.consultation', compact('patient'));
    }

    /**
     * Simple billing receipt form.
     */
    public function billing(Request $request): View
    {
        $patient = $this->findFacilityPatient($request->query('patient_id'), $this->facilityId());

        if ($patient) {
            $this->ctx->auditPatientAccess(
                actionType:   'lite_billing_view',
                resourceType: 'Patient',
                resourceId:   $patient->id,
                patientId:    $patient->id,
            );
        }

        return view('portals.lite.billing', compact('patient'));
    }

    // ------------------------------------------------------------------
    // Device management (admin-level views)
    // ------------------------------------------------------------------

    /**
     * List all Lite devices for the facility.
     */
    public function devices(): View
    {
        $facilityId = $this->facilityId();
        $devices    = LiteDevice::where('facility_id', $facilityId)
            ->with(['config', 'entitlements'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = $this->liteService->getAdminStats($facilityId);

        return view('portals.lite.devices', compact('devices', 'stats'));
    }

    /**
     * Activate a pending device.
     */
    public function activateDevice(LiteDevice $device): RedirectResponse
    {
        if ($device->facility_id !== $this->facilityId()) {
            abort(403);
        }

        $this->liteService->activateDevice($device);

        return back()->with('success', __('lite_chrome.flash_device_activated', ['name' => $device->device_name]));
    }

    /**
     * Revoke a device.
     */
    public function revokeDevice(Request $request, LiteDevice $device): RedirectResponse
    {
        if ($device->facility_id !== $this->facilityId()) {
            abort(403);
        }

        $reason = $request->input('reason', 'Revoked by administrator.');
        $this->liteService->revokeDevice($device, $reason);

        return back()->with('success', __('lite_chrome.flash_device_revoked', ['name' => $device->device_name]));
    }

    /**
     * List open sync conflicts for the facility.
     */
    public function conflicts(): View
    {
        $facilityId = $this->facilityId();
        $deviceIds  = LiteDevice::where('facility_id', $facilityId)->pluck('id');

        $conflicts = LiteConflict::whereIn('lite_device_id', $deviceIds)
            ->with(['device', 'offlineEvent'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('portals.lite.conflicts', compact('conflicts'));
    }

    /**
     * Resolve or dismiss a conflict.
     */
    public function resolveConflict(Request $request, LiteConflict $conflict): RedirectResponse
    {
        // lite_conflicts carries no facility_id of its own — ownership lives on
        // the device that raised it. Route-model binding resolves the conflict
        // by id alone, so without this every other facility's sync conflicts
        // (and the offline clinical events behind them) could be resolved or
        // dismissed from here. The sibling device actions already check this;
        // this one did not.
        $facilityId = $this->facilityId();

        abort_unless(
            LiteDevice::where('id', $conflict->lite_device_id)
                ->where('facility_id', $facilityId)
                ->exists(),
            403
        );

        $data = $request->validate([
            'resolution' => 'required|in:resolved,dismiss',
            'note'       => 'nullable|string|max:500',
        ]);

        $this->liteService->resolveConflict(
            $conflict,
            $this->demoActorId(),
            $data['resolution'],
            $data['note'] ?? ''
        );

        return back()->with('success', __('flash.conflict_resolved', ['resolution' => $data['resolution']]));
    }

    /**
     * Offline events log for a device.
     */
    public function offlineEvents(LiteDevice $device): View
    {
        if ($device->facility_id !== $this->facilityId()) {
            abort(403);
        }

        $events = LiteOfflineEvent::where('lite_device_id', $device->id)
            ->orderByDesc('captured_at')
            ->paginate(30);

        return view('portals.lite.offline_events', compact('device', 'events'));
    }
}
