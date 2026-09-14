<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Services\Lab\LabDeskWebService;
use App\Services\Portal\PortalContextService;
use App\Support\StaffCapabilities;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The laboratory desk — the web portal's door onto lab orders.
 *
 * `lab_orders` and `lab_results` had a clinician-app desk (routes/
 * mobile_lab_desk.php) and no web page at all: the `lab_results` and
 * `sample_collection` capabilities were granted to five roles and gated
 * nothing. This controller is that page, and it is only a surface — every
 * rule lives in App\Services\Lab\LabDeskWebService (and, through it, in the
 * bench's own LabDeskService).
 *
 * SCOPE. The facility is read from PortalContextService — the web tier's
 * convention — and never from the request. An order at another facility is
 * not refused, it does not exist (404). The role gate is `capability:lab_results`
 * on the routes (403 role_forbidden); the collection routes also admit
 * `sample_collection`; validation is additionally role-checked in the service.
 *
 * NOTHING HERE READS BILLING. Care is never gated on payment.
 */
class LabDeskWebController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly LabDeskWebService $desk,
    ) {
    }

    /** The worklist, one status column at a time; exact Health ID narrows it. */
    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();

        $validated = $request->validate([
            'status'    => ['nullable', 'string', Rule::in(LabDeskWebService::STATUSES)],
            'health_id' => ['nullable', 'string', 'max:64'],
        ]);

        $status   = $validated['status'] ?? 'ordered';
        $healthId = isset($validated['health_id']) ? trim((string) $validated['health_id']) : null;

        $orders = $this->desk->worklist($facilityId, $status, $healthId ?: null);

        $this->context->auditPatientAccess('lab_desk_worklist', 'LabOrder', null, null, [
            'facility_id' => $facilityId,
        ]);

        return view('portals.lab.index', [
            'orders'   => $orders,
            'status'   => $status,
            'healthId' => $healthId,
            'counts'   => $this->desk->counts($facilityId),
            'statuses' => LabDeskWebService::STATUSES,
        ]);
    }

    /** Pending draws. */
    public function collection(Request $request): View
    {
        $facilityId = $this->facilityId();

        return view('portals.lab.collection', [
            'orders' => $this->desk->pendingCollection($facilityId),
        ]);
    }

    /** The sample is in. */
    public function collect(Request $request, string $id): RedirectResponse
    {
        $order = $this->order($id);

        $validated = $request->validate([
            'specimen_type' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $this->desk->collect($order, (string) auth()->id(), $validated['specimen_type'] ?? null);
        } catch (HttpResponseException $e) {
            return $this->refused($e);
        }

        $this->context->auditPatientAccess('lab_desk_sample_collection', 'LabOrder', $order->id, $order->patient_id);

        return redirect()->route('portals.lab.collection')->with('success', __('lab_desk.collected'));
    }

    /** One order: its results, their flags, the alert state, and the entry form. */
    public function show(Request $request, string $id): View
    {
        $order = $this->order($id);

        $this->context->auditPatientAccess('lab_desk_review', 'LabOrder', $order->id, $order->patient_id);

        return view('portals.lab.show', $this->desk->detail($order) + [
            'canValidate' => in_array((string) $this->actorRole(), LabDeskWebService::VALIDATOR_ROLES, true),
            'actorId'     => (string) auth()->id(),
        ]);
    }

    /** Enter one analyte — a new row, never an update. */
    public function storeResult(Request $request, string $id): RedirectResponse
    {
        $order = $this->order($id);

        $validated = $request->validate([
            'parameter_name' => ['nullable', 'string', 'max:200'],
            'value'          => ['required', 'string', 'max:100'],
            'unit'           => ['nullable', 'string', 'max:50'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->desk->enterResult($order, $validated, (string) auth()->id());
        } catch (HttpResponseException $e) {
            return $this->refused($e);
        }

        $this->context->auditPatientAccess('lab_desk_result_entry', 'LabOrder', $order->id, $order->patient_id);

        return redirect()->route('portals.lab.show', $order->id)->with('success', __('lab_desk.result_entered'));
    }

    /** Correct a result: a new row that names the one it supersedes. */
    public function correct(Request $request, string $id, string $resultId): RedirectResponse
    {
        $order = $this->order($id);

        $original = Str::isUuid($resultId)
            ? LabResult::query()->where('lab_order_id', $order->id)->find($resultId)
            : null;

        if ($original === null) {
            abort(404, __('lab_desk.err.result_not_found'));
        }

        $validated = $request->validate([
            'value'  => ['required', 'string', 'max:100'],
            'unit'   => ['nullable', 'string', 'max:50'],
            'reason' => ['required', 'string', 'max:1000'],
            'notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->desk->correct($order, $original, $validated, $validated['reason'], (string) auth()->id());
        } catch (HttpResponseException $e) {
            return $this->refused($e);
        }

        $this->context->auditPatientAccess('lab_desk_result_correction', 'LabResult', $original->id, $order->patient_id, [
            'facility_id' => $this->facilityId(),
        ]);

        return redirect()->route('portals.lab.show', $order->id)->with('success', __('lab_desk.result_corrected'));
    }

    /** Release the panel to the patient and the ordering clinician. */
    public function release(Request $request, string $id): RedirectResponse
    {
        $order = $this->order($id);

        try {
            $this->desk->release($order, (string) auth()->id());
        } catch (HttpResponseException $e) {
            return $this->refused($e);
        }

        $this->context->auditPatientAccess('lab_desk_release', 'LabOrder', $order->id, $order->patient_id);

        return redirect()->route('portals.lab.show', $order->id)->with('success', __('lab_desk.released'));
    }

    /** Second-person validation. */
    public function validateOrder(Request $request, string $id): RedirectResponse
    {
        $order = $this->order($id);

        try {
            $this->desk->validate($order, (string) auth()->id(), $this->actorRole());
        } catch (HttpResponseException $e) {
            return $this->refused($e);
        }

        $this->context->auditPatientAccess('lab_desk_validation', 'LabOrder', $order->id, $order->patient_id);

        return redirect()->route('portals.lab.show', $order->id)->with('success', __('lab_desk.validated'));
    }

    // ── internals ─────────────────────────────────────────────────────────

    /** The order in THIS facility's scope, or 404 — whether it exists elsewhere or not at all. */
    private function order(string $id): LabOrder
    {
        return $this->desk->find($this->facilityId(), $id)
            ?? abort(404, __('lab_desk.err.order_not_found'));
    }

    /**
     * PortalContextService only — the session's active facility, then the
     * user's primary posting. Never a header, never the body, never a
     * "first facility in the table" fallback.
     */
    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('lab_desk.err.no_facility_context'));
        }

        return (string) $facilityId;
    }

    private function actorRole(): ?string
    {
        $user = auth()->user();

        return $user ? StaffCapabilities::roleFor($user, $this->context->facilityId()) : null;
    }

    /** A service refusal ({message, error_code} JSON) becomes a flash on the page the user is on. */
    private function refused(HttpResponseException $e): RedirectResponse
    {
        $response = $e->getResponse();
        $data     = json_decode((string) $response->getContent(), true) ?: [];

        if (($response->getStatusCode()) === 404) {
            abort(404, (string) ($data['message'] ?? ''));
        }

        return back()->withInput()->with('error', (string) ($data['message'] ?? __('lab_desk.err.refused')));
    }
}
