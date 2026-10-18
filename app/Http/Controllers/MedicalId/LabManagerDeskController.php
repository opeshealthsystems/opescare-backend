<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use App\Models\LabTestRequest;
use App\Models\Patient;
use App\Services\Lab\LabDeskWebService;
use App\Services\Lab\LabManagerDeskService;
use App\Services\Patient\PatientDocumentService;
use App\Services\Portal\PortalContextService;
use App\Support\StaffCapabilities;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The laboratory manager's desk — the landing dashboard, the patient
 * requests inbox, sample rejection and the bench's printed report.
 *
 * A surface only: every rule lives in LabManagerDeskService and, through it,
 * in the bench's own LabDeskService. Facility from PortalContextService, never
 * the request; another facility's row is 404, never 403. The role gate is
 * `capability:lab_results` on the routes (routes/lab_manager.php).
 *
 * NOTHING HERE READS BILLING. Care is never gated on payment.
 */
class LabManagerDeskController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly LabManagerDeskService $desk,
        private readonly LabDeskWebService $web,
        private readonly PatientDocumentService $documents,
    ) {
    }

    /** The landing page: live figures for this laboratory. */
    public function dashboard(Request $request): View
    {
        $facilityId = $this->facilityId();

        $this->context->auditPatientAccess('lab_manager_dashboard', 'LabOrder', null, null, [
            'facility_id' => $facilityId,
        ]);

        $dashboard = $this->desk->dashboard($facilityId);

        return view('portals.lab.dashboard', $dashboard + $this->desk->bench($facilityId, $dashboard['turnaround']) + [
            'facilityName' => $this->desk->facilityName($facilityId),
            'statuses'     => LabDeskWebService::STATUSES,
            'publication'  => $this->desk->publication($facilityId),
            'hasAnyWork'   => $this->desk->hasAnyWork($facilityId),
            'canCatalogue' => StaffCapabilities::canAt(auth()->user(), $facilityId, 'service_catalogue'),
        ]);
    }

    /**
     * Record the laboratory's telephone call-back of a critical value to a
     * named person. Closes the alert once; never re-stamps it.
     */
    public function criticalCallback(Request $request, string $id): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $alert = $this->desk->findCriticalAlert($facilityId, $id)
            ?? abort(404, __('lab_manager.err.alert_not_found'));

        $validated = $request->validate([
            'informed' => ['required', 'string', 'min:2', 'max:160'],
            'note'     => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->desk->recordCriticalCallback($alert, (string) auth()->id(), $validated['informed'], (string) ($validated['note'] ?? ''));
        } catch (HttpResponseException $e) {
            return $this->refused($e);
        }

        $this->context->auditPatientAccess('lab_manager_critical_callback', 'CriticalValueAlert', $alert->id, $alert->patient_id, [
            'facility_id' => $facilityId,
        ]);

        return redirect()->route('portals.lab_manager.dashboard')->with('success', __('lab_manager.callback_recorded'));
    }

    /** Requests the patients of this facility have sent it. */
    public function requests(Request $request): View
    {
        $facilityId = $this->facilityId();

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['submitted', 'approved', 'declined', 'cancelled'])],
        ]);

        $status = $validated['status'] ?? 'submitted';

        $this->context->auditPatientAccess('lab_manager_requests', 'LabTestRequest', null, null, [
            'facility_id' => $facilityId,
        ]);

        return view('portals.lab.requests', [
            'requests' => $this->desk->requests($facilityId, $status),
            'status'   => $status,
            'counts'   => $this->desk->requestCounts($facilityId),
        ]);
    }

    /** Approve: the request becomes a real order on this laboratory's worklist. */
    public function approveRequest(Request $request, string $id): RedirectResponse
    {
        $facilityId  = $this->facilityId();
        $testRequest = $this->request($facilityId, $id);

        try {
            $decided = $this->desk->approveRequest($testRequest, $facilityId, (string) auth()->id());
        } catch (HttpResponseException $e) {
            return $this->refused($e);
        }

        $this->context->auditPatientAccess('lab_manager_request_approved', 'LabTestRequest', $decided->id, $decided->patient_id);

        return redirect()->route('portals.lab_manager.requests')->with('success', __('lab_manager.request_approved'));
    }

    /** Decline, with the reason the patient will read. */
    public function declineRequest(Request $request, string $id): RedirectResponse
    {
        $facilityId  = $this->facilityId();
        $testRequest = $this->request($facilityId, $id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        try {
            $decided = $this->desk->declineRequest($testRequest, (string) auth()->id(), $validated['reason']);
        } catch (HttpResponseException $e) {
            return $this->refused($e);
        }

        $this->context->auditPatientAccess('lab_manager_request_declined', 'LabTestRequest', $decided->id, $decided->patient_id);

        return redirect()->route('portals.lab_manager.requests')->with('success', __('lab_manager.request_declined'));
    }

    /** Refuse the sample, with a reason. Final. */
    public function reject(Request $request, string $id): RedirectResponse
    {
        $order = $this->order($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        try {
            $this->desk->reject($order, $validated['reason'], (string) auth()->id());
        } catch (HttpResponseException $e) {
            return $this->refused($e);
        }

        $this->context->auditPatientAccess('lab_manager_sample_rejected', 'LabOrder', $order->id, $order->patient_id, [
            'facility_id' => $this->facilityId(),
        ]);

        return redirect()->route('portals.lab.show', $order->id)->with('success', __('lab_manager.rejected'));
    }

    /**
     * The printed report — the SAME Secure-sheet PDF the patient downloads,
     * generated now and never stored, with a verification row that names the
     * bench user who issued it. Only a released or validated order is a report.
     */
    public function report(Request $request, string $id): StreamedResponse
    {
        $order = $this->order($id);

        if (! $order->isReleased()) {
            abort(404, __('lab_manager.err.not_released'));
        }

        // The patient by id, past the demo partition: the order is already in
        // this facility's scope and the sheet must name whose result it is.
        $patient = Patient::query()->withoutGlobalScopes()->find($order->patient_id);

        if ($patient === null) {
            abort(404, __('lab_desk.err.order_not_found'));
        }

        $this->context->auditPatientAccess('lab_manager_report_printed', 'LabOrder', $order->id, $order->patient_id, [
            'facility_id' => $this->facilityId(),
        ]);

        return $this->documents->download($patient, PatientDocumentService::LABORATORY_REPORT, $order->id, (string) auth()->id());
    }

    // ── internals ─────────────────────────────────────────────────────────

    private function order(string $id): LabOrder
    {
        return $this->web->find($this->facilityId(), $id)
            ?? abort(404, __('lab_desk.err.order_not_found'));
    }

    private function request(string $facilityId, string $id): LabTestRequest
    {
        return $this->desk->findRequest($facilityId, $id)
            ?? abort(404, __('lab_manager.err.request_not_found'));
    }

    /** PortalContextService only — never a header, the body, or a fallback. */
    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('lab_desk.err.no_facility_context'));
        }

        return (string) $facilityId;
    }

    /** A service refusal ({message, error_code} JSON) becomes a flash on the page the user is on. */
    private function refused(HttpResponseException $e): RedirectResponse
    {
        $response = $e->getResponse();
        $data     = json_decode((string) $response->getContent(), true) ?: [];

        if ($response->getStatusCode() === 404) {
            abort(404, (string) ($data['message'] ?? ''));
        }

        return back()->withInput()->with('error', (string) ($data['message'] ?? __('lab_desk.err.refused')));
    }
}
