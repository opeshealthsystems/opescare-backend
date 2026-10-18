<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\CareFacility;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\Encounter;
use App\Models\Facility;
use App\Models\ImagingOrder;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Receipt;
use App\Models\User;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Documents\NewDocumentRenderer;
use App\Services\Imaging\ImagingDeskService;
use App\Services\Pharmacy\PharmacyDeskException;
use App\Services\Pharmacy\PharmacyDeskService;
use App\Services\Portal\DeskFacilityContext;
use App\Services\Referral\ReferralDeskService;
use App\Support\ClinicalRelationshipGate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Facility-side documents built in the "missing documents" pass
 * (docs/specs/documents/20-30). Every action:
 *
 *   facility  ← DeskFacilityContext (the session posting), never the request
 *   record    ← looked up WITH that facility; not ours → 404
 *   consent   ← ClinicalRelationshipGate for clinical narrative (referral,
 *               encounter); orders/billing are the facility's own episode
 *   audit     ← access log written first; unwritable → 503, no document
 *   render    ← NewDocumentRenderer, on demand, never stored
 */
class NewFacilityDocumentController extends Controller
{
    public function __construct(
        private readonly NewDocumentRenderer $renderer,
        private readonly DeskFacilityContext $desk,
    ) {
    }

    // ── 20 Referral / counter-referral ───────────────────────────────────
    public function referral(string $id, ReferralDeskService $referrals): Response
    {
        $facilityId = $this->facilityId();
        $referral   = $referrals->findForFacility($id, $facilityId) ?? abort(404);
        $patient    = $this->patient($referral->patient_id);

        $this->gate($patient, $facilityId);
        $this->audit($patient, $facilityId, 'treatment:referral', 'referral', 'ReferralCase', $referral->id);

        return $this->renderer->secure('documents.new.referral-letter', $patient, 'referral_letter', [
            'referral'  => $referral,
            'from'      => Facility::find($referral->referring_facility_id),
            'to'        => $referral->receiving_facility_id ? Facility::find($referral->receiving_facility_id) : null,
            'referrer'  => $referral->referring_provider_id ? User::find($referral->referring_provider_id) : null,
        ], 'referral', (string) auth()->id());
    }

    // ── 21 Encounter report ──────────────────────────────────────────────
    public function encounter(string $id): Response
    {
        $facilityId = $this->facilityId();
        abort_unless(Str::isUuid($id), 404);
        $encounter = Encounter::query()->where('facility_id', $facilityId)->find($id) ?? abort(404);
        $patient   = $this->patient($encounter->patient_id);

        $this->gate($patient, $facilityId);
        $this->audit($patient, $facilityId, 'treatment', 'encounter', 'Encounter', $encounter->id);

        $visitId = $encounter->visit_id;
        $byVisit = fn ($query) => $visitId
            // The visit is the encounter's own; clinical_notes.patient_id is
            // not always written, so the visit alone scopes the rows.
            ? $query->where('visit_id', $visitId)->get()
            : collect();

        return $this->renderer->secure('documents.new.encounter-report', $patient, 'encounter_report', [
            'encounter'     => $encounter,
            'facility'      => Facility::find($facilityId),
            'attending'     => $encounter->attending_provider_id ? User::find($encounter->attending_provider_id) : null,
            'diagnoses'     => $byVisit(Diagnosis::query()->orderByDesc('is_primary')),
            'notes'         => $byVisit(ClinicalNote::query()->whereNotNull('signed_at')->orderBy('signed_at')),
            'prescriptions' => $byVisit(Prescription::query()->with('items')->whereNull('voided_at')->orderBy('prescribed_at')),
            'labOrders'     => $byVisit(LabOrder::query()->orderBy('ordered_at')),
            'imagingOrders' => $byVisit(ImagingOrder::query()->orderBy('ordered_at')),
        ], 'encounter-report', (string) auth()->id());
    }

    // ── 22 Invoice ───────────────────────────────────────────────────────
    public function invoice(string $id): Response
    {
        $facilityId = $this->facilityId();
        abort_unless(Str::isUuid($id), 404);
        $invoice = Invoice::query()->with('items')->where('facility_id', $facilityId)->find($id) ?? abort(404);
        $patient = $this->patient($invoice->patient_id);

        $this->audit($patient, $facilityId, 'billing', 'billing', 'Invoice', $invoice->id);

        return $this->renderer->secure('documents.new.invoice', $patient, 'invoice', [
            'invoice'   => $invoice,
            'facility'  => Facility::find($facilityId),
            'watermark' => $invoice->voided_at ? __('doc_new_docs.watermark_void') : null,
        ], 'invoice', (string) auth()->id());
    }

    // ── 23 Payment receipt ───────────────────────────────────────────────
    public function receipt(string $id): Response
    {
        $facilityId = $this->facilityId();
        abort_unless(Str::isUuid($id), 404);
        $receipt = Receipt::query()
            ->whereIn('invoice_id', Invoice::query()->where('facility_id', $facilityId)->select('id'))
            ->find($id) ?? abort(404);
        $invoice = Invoice::query()->findOrFail($receipt->invoice_id);
        $payment = Payment::query()->find($receipt->payment_id);
        $patient = $this->patient($invoice->patient_id);

        $this->audit($patient, $facilityId, 'billing', 'billing', 'Receipt', $receipt->id);

        return $this->renderer->secure('documents.new.receipt', $patient, 'payment_receipt', [
            'receipt'  => $receipt,
            'invoice'  => $invoice,
            'payment'  => $payment,
            'cashier'  => $payment?->cashier_id ? User::find($payment->cashier_id) : null,
            'facility' => Facility::find($facilityId),
        ], 'receipt', (string) auth()->id());
    }

    // ── 27 Dispensing labels ─────────────────────────────────────────────
    public function labels(string $id, PharmacyDeskService $pharmacy): Response
    {
        $facility = $pharmacy->facility($this->facilityId()) ?? abort(403);

        try {
            $rx = $pharmacy->find($facility, $id);
        } catch (PharmacyDeskException $e) {
            abort($e->status === 404 ? 404 : 403);
        }

        $rx->loadMissing(['items.medicine', 'patient']);
        $items = $rx->items->filter(fn ($item) => $item->dispensed_at !== null)->values();
        abort_if($items->isEmpty(), 404, __('doc_new_docs.labels_none'));

        $patient = $rx->patient;
        if ($patient) {
            $this->audit($patient, (string) $facility->id, 'treatment:dispensing', 'medication', 'Prescription', $rx->id);
        }

        // 100 x 50 mm landscape, in points.
        return $this->renderer->plain('documents.new.dispensing-label', [
            'prescription' => $rx,
            'items'        => $items,
            'patient'      => $patient,
            'facility'     => $facility,
            'reference'    => $rx->reference,
        ], [0, 0, 283.46, 141.73], 'labels', $patient);
    }

    // ── 28 Lab request ───────────────────────────────────────────────────
    public function labRequest(string $id): Response
    {
        $facilityId = $this->facilityId();
        abort_unless(Str::isUuid($id), 404);
        $order   = LabOrder::query()->where('facility_id', $facilityId)->find($id) ?? abort(404);
        $patient = $this->patient($order->patient_id);

        $this->audit($patient, $facilityId, 'treatment', 'laboratory', 'LabOrder', $order->id);

        return $this->renderer->secure('documents.new.lab-request', $patient, 'lab_request', [
            'order'     => $order,
            'requester' => $order->ordered_by ? User::find($order->ordered_by) : null,
            'facility'  => Facility::find($facilityId),
        ], 'lab-request', (string) auth()->id());
    }

    // ── 29 Imaging request ───────────────────────────────────────────────
    public function imagingRequest(string $id, ImagingDeskService $imaging): Response
    {
        $facilityId = $this->facilityId();
        $order      = $imaging->find($facilityId, $id) ?? abort(404);
        $patient    = $this->patient($order->patient_id);

        $this->audit($patient, $facilityId, 'treatment', 'imaging', 'ImagingOrder', $order->id);

        return $this->renderer->secure('documents.new.imaging-request', $patient, 'imaging_request', [
            'order'     => $order,
            'requester' => $order->ordered_by ? User::find($order->ordered_by) : null,
            'facility'  => Facility::find($facilityId),
        ], 'imaging-request', (string) auth()->id());
    }

    // ── 30 Blood request slip ────────────────────────────────────────────
    public function bloodRequest(string $id): Response
    {
        $facilityId = $this->facilityId();
        abort_unless(Str::isUuid($id), 404);

        $listingIds = CareFacility::query()->where('facility_id', $facilityId)->pluck('id');
        $request    = BloodRequest::query()->with(['careFacility', 'patient'])
            ->whereIn('care_facility_id', $listingIds)
            ->find($id) ?? abort(404);

        $patient = $request->patient_id ? Patient::withoutGlobalScopes()->find($request->patient_id) : null;
        if ($patient) {
            $this->audit($patient, $facilityId, 'treatment:transfusion', 'blood', 'BloodRequest', $request->id);
        }

        return $this->renderer->secure('documents.new.blood-request', $patient, 'blood_request', [
            'bloodRequest' => $request,
            'reference'    => $request->reference,
            'facility'     => Facility::find($facilityId),
        ], 'blood-request', (string) auth()->id());
    }

    // ── shared ───────────────────────────────────────────────────────────
    private function facilityId(): string
    {
        $facilityId = $this->desk->resolve();
        abort_if(! $facilityId, 403);

        return (string) $facilityId;
    }

    private function patient(?string $id): Patient
    {
        // The demo partition scope must not turn a real record into a 404 on
        // a desk that legitimately holds it; the facility filter already ran.
        return ($id ? Patient::withoutGlobalScopes()->find($id) : null) ?? abort(404);
    }

    /** Clinical narrative needs a relationship basis (consent, episode, care team …). */
    private function gate(Patient $patient, string $facilityId): void
    {
        $verdict = ClinicalRelationshipGate::evaluate((string) auth()->id(), $facilityId, $patient->id);
        abort_unless($verdict->holds(), 403, __('doc_new_docs.consent_required'));
    }

    private function audit(Patient $patient, string $facilityId, string $purpose, string $category, string $type, ?string $resourceId): void
    {
        try {
            AccessLogService::log(
                patientId: $patient->id,
                actorId: (string) auth()->id(),
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: $purpose,
                dataCategory: $category,
                resourceType: $type,
                resourceId: $resourceId,
                accessType: 'print',
            );
        } catch (\Throwable $e) {
            Log::error('Document refused: access log could not be written.', ['type' => $type, 'error' => $e->getMessage()]);
            abort(503, __('doc_new_docs.audit_unavailable'));
        }
    }
}
