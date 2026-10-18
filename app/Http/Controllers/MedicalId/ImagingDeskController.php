<?php

namespace App\Http\Controllers\MedicalId;

use App\Enums\ImagingOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ImagingOrder;
use App\Models\Patient;
use App\Services\Imaging\ImagingDeskService;
use App\Services\Portal\PortalContextService;
use App\Support\ResultFlagResolver;
use App\Support\StaffCapabilities;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The imaging desk — the web portal's door onto imaging orders.
 *
 * Only a surface: every rule lives in App\Services\Imaging\ImagingDeskService.
 *
 * SCOPE. The facility is read from PortalContextService and never from the
 * request. An order at another facility does not exist (404). The role gate
 * is `capability:imaging` on the routes (403 role_forbidden).
 *
 * PRIVACY. The pages show a patient's AGE BAND, never the date of birth.
 * Every patient read is written to the access log through
 * PortalContextService::auditPatientAccess.
 */
class ImagingDeskController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly ImagingDeskService $desk,
    ) {
    }

    /** The worklist, one tab at a time. */
    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();

        $validated = $request->validate([
            'tab'       => ['nullable', 'string', Rule::in(ImagingDeskService::TABS)],
            'modality'  => ['nullable', 'string', Rule::in(ImagingDeskService::MODALITIES)],
            'urgency'   => ['nullable', 'string', Rule::in(ImagingDeskService::URGENCIES)],
            'date'      => ['nullable', 'date'],
            'health_id' => ['nullable', 'string', 'max:64'],
        ]);

        $filters = array_filter($validated, fn ($v) => $v !== null && $v !== '');
        $filters['tab'] ??= 'pending';

        $orders = $this->desk->worklist($facilityId, $filters);

        $this->context->auditPatientAccess('imaging_desk_worklist', 'ImagingOrder', null, null, [
            'facility_id' => $facilityId,
            'tab'         => $filters['tab'],
        ]);

        return view('portals.imaging.index', [
            'orders'     => $orders,
            'filters'    => $filters,
            'tab'        => $filters['tab'],
            'counts'     => $this->desk->counts($facilityId),
            'tabs'       => ImagingDeskService::TABS,
            'modalities' => ImagingDeskService::MODALITIES,
            'urgencies'  => ImagingDeskService::URGENCIES,
            'canReport'  => StaffCapabilities::canAt(auth()->user(), $facilityId, 'imaging_report'),
        ]);
    }

    /**
     * Intake. The patient arrives by EXACT Health ID — no name search, no
     * partial match — the same rule as the triage and prescriber desks.
     */
    public function create(Request $request): View
    {
        $this->facilityId();

        $patient  = null;
        $notFound = false;

        if ($healthId = trim((string) $request->input('health_id'))) {
            $patient  = Patient::resolveHealthId($healthId);
            $notFound = $patient === null;

            if ($patient) {
                $this->context->auditPatientAccess('imaging_desk_intake_lookup', 'Patient', $patient->id, $patient->id, [
                    'facility_id' => $this->facilityId(),
                ]);
            }
        }

        return view('portals.imaging.create', [
            'patient'    => $patient,
            'ageBand'    => $patient ? $this->ageBand($patient) : null,
            'notFound'   => $notFound,
            'modalities' => ImagingDeskService::MODALITIES,
            'urgencies'  => ImagingDeskService::URGENCIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $validated = $request->validate([
            'patient_id'          => ['required', 'uuid'],
            'modality'            => ['required', 'string', Rule::in(ImagingDeskService::MODALITIES)],
            'body_part'           => ['nullable', 'string', 'max:100'],
            'clinical_indication' => ['required', 'string', 'max:2000'],
            'urgency'             => ['required', 'string', Rule::in(ImagingDeskService::URGENCIES)],
            'referring_physician' => ['nullable', 'string', 'max:255'],
            'notes'               => ['nullable', 'string', 'max:2000'],
            'scheduled_at'        => ['nullable', 'date'],
        ]);

        try {
            $order = $this->desk->create($facilityId, auth()->user(), $validated);
        } catch (HttpResponseException $e) {
            return $this->refused($e);
        }

        $this->context->auditPatientAccess('imaging_desk_intake', 'ImagingOrder', $order->id, $order->patient_id, [
            'facility_id' => $facilityId,
        ]);

        return redirect()->route('portals.imaging.show', $order->id)
            ->with('success', __('imaging_desk.created', ['accession' => $order->accession_number]));
    }

    public function show(Request $request, string $id): View
    {
        $order = $this->order($id);

        $this->context->auditPatientAccess('imaging_desk_review', 'ImagingOrder', $order->id, $order->patient_id, [
            'facility_id' => $this->facilityId(),
        ]);

        $status = $order->status instanceof ImagingOrderStatus ? $order->status : ImagingOrderStatus::from((string) $order->status);

        return view('portals.imaging.show', [
            'order'     => $order,
            'status'    => $status,
            'ageBand'   => $this->ageBand($order->patient),
            'timeline'  => $this->desk->timeline($order),
            'canReport' => StaffCapabilities::canAt(auth()->user(), $this->facilityId(), 'imaging_report'),
        ]);
    }

    public function schedule(Request $request, string $id): RedirectResponse
    {
        $order = $this->order($id);

        $validated = $request->validate(['scheduled_at' => ['required', 'date']]);

        return $this->transition($order, fn () => $this->desk->schedule($order, $validated['scheduled_at']), 'imaging_desk_scheduled', 'imaging_desk.scheduled');
    }

    public function start(Request $request, string $id): RedirectResponse
    {
        $order = $this->order($id);

        return $this->transition($order, fn () => $this->desk->start($order), 'imaging_desk_started', 'imaging_desk.started');
    }

    public function complete(Request $request, string $id): RedirectResponse
    {
        $order = $this->order($id);

        return $this->transition($order, fn () => $this->desk->complete($order), 'imaging_desk_completed', 'imaging_desk.completed');
    }

    public function cancel(Request $request, string $id): RedirectResponse
    {
        $order = $this->order($id);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        return $this->transition($order, fn () => $this->desk->cancel($order, $validated['reason']), 'imaging_desk_cancelled', 'imaging_desk.cancelled');
    }

    // ── internals ─────────────────────────────────────────────────────────

    private function transition(ImagingOrder $order, callable $do, string $auditAction, string $flashKey): RedirectResponse
    {
        try {
            $do();
        } catch (HttpResponseException $e) {
            return $this->refused($e);
        }

        $this->context->auditPatientAccess($auditAction, 'ImagingOrder', $order->id, $order->patient_id, [
            'facility_id' => $this->facilityId(),
        ]);

        return redirect()->route('portals.imaging.show', $order->id)->with('success', __($flashKey));
    }

    private function order(string $id): ImagingOrder
    {
        return $this->desk->find($this->facilityId(), $id)
            ?? abort(404, __('imaging_desk.err.order_not_found'));
    }

    /** The age band (neonate … geriatric), never the date of birth. */
    private function ageBand(?Patient $patient): ?string
    {
        try {
            $dob = $patient?->date_of_birth;
        } catch (\Throwable) {
            $dob = null;
        }

        return $dob ? ResultFlagResolver::ageBand($dob) : null;
    }

    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        if (! $facilityId) {
            abort(403, __('imaging_desk.err.no_facility_context'));
        }

        return (string) $facilityId;
    }

    private function refused(HttpResponseException $e): RedirectResponse
    {
        $response = $e->getResponse();
        $data     = json_decode((string) $response->getContent(), true) ?: [];

        if ($response->getStatusCode() === 404) {
            abort(404, (string) ($data['message'] ?? ''));
        }

        return back()->withInput()->with('error', (string) ($data['message'] ?? __('imaging_desk.err.refused')));
    }
}
