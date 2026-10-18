<?php

namespace App\Services\Clinical;

use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\Documents\DocumentIssuanceService;
use App\Services\Portal\PortalContextService;
use Illuminate\Support\Facades\DB;

/**
 * The single write path for a prescription — portal and API both come through
 * here, so the two cannot drift.
 *
 * Before this existed the only writer was PrescriptionController::store(), and
 * it wrote columns that do not exist (`prescriber_id`, `is_discharge_prescription`)
 * and item keys that are not fillable (`dosage`, `duration`). Mass assignment
 * dropped them silently: every prescription created through the API had a null
 * prescriber and items with no dose. Normalising those aliases here fixes the
 * API and gives the portal one correct path to reuse.
 *
 * Everything this service writes obeys the platform's clinical-event rule: a
 * prescription is issued once, dispensed once, and corrected only by amendment,
 * void, or entered-in-error — never by overwriting. The Prescription model
 * enforces that; this service is where the legitimate transitions live.
 *
 * Deliberately NOT here: drug-interaction and allergy checking. That is the
 * clinical_decision_support module, frozen out of V1.
 */
class PrescriptionService
{
    /** Default validity of a new prescription when the prescriber gives none. */
    public const DEFAULT_VALIDITY_DAYS = 30;

    public function __construct(
        private readonly PortalContextService $ctx,
        private readonly DocumentIssuanceService $issuance,
    ) {}

    /**
     * Issue a new prescription with its items.
     *
     * @param  array{
     *     patient_id:string, facility_id:string, prescribed_by?:?string, visit_id?:?string,
     *     notes?:?string, expires_at?:?string, validity_days?:?int, items:array<int,array<string,mixed>>,
     *     amends_prescription_id?:?string
     * }  $data
     * @param  string|null  $actorId  Explicit actor for non-portal callers (API clients).
     */
    public function issue(array $data, ?string $actorId = null): Prescription
    {
        if (empty($data['items'])) {
            throw new \InvalidArgumentException('PRESCRIPTION_REQUIRES_AT_LEAST_ONE_ITEM');
        }

        $facilityId = $data['facility_id'];
        $items      = array_map(fn (array $item) => $this->normaliseItem($item), $data['items']);

        $prescription = DB::transaction(function () use ($data, $facilityId, $items) {
            $prescription = Prescription::create([
                'patient_id'             => $data['patient_id'],
                'facility_id'            => $facilityId,
                'visit_id'               => $data['visit_id'] ?? null,
                'prescribed_by'          => $data['prescribed_by'] ?? null,
                'notes'                  => $data['notes'] ?? null,
                'status'                 => 'active',
                'prescribed_at'          => now(),
                'expires_at'             => $this->resolveExpiry($data),
                'amends_prescription_id' => $data['amends_prescription_id'] ?? null,
            ]);

            foreach ($items as $item) {
                PrescriptionItem::create($item + [
                    'prescription_id' => $prescription->id,
                    'status'          => 'pending',
                ]);
            }

            return $prescription;
        });

        // Non-fatal: an unissued RX document must never lose the clinical record.
        try {
            $this->issuance->issueFromModel(
                'RX',
                'Prescription — ' . count($items) . ' item(s)',
                [
                    'prescription_id' => $prescription->id,
                    'patient_id'      => $data['patient_id'],
                    'items'           => $items,
                ],
                $facilityId,
                $data['patient_id'],
                null,
                $data['prescribed_by'] ?? $actorId,
            );
        } catch (\Throwable $e) {
            // Document issuance failure is non-fatal.
        }

        $this->audit('prescription_issued', $prescription, $actorId, [
            'reason' => 'Prescription issued with ' . count($items) . ' item(s)',
        ]);

        // Partner webhooks (prescription.issued) — queued, signed, never fatal.
        \App\Modules\Connect\Services\WebhookEventPublisher::prescriptionIssued($prescription);

        // The patient is told, with the code. The code already reached the
        // printed sheet and the patient portal; without a notification a
        // patient who left without the paper had no prompt to look for it.
        // Best effort: a notification failure never undoes the prescription.
        rescue(function () use ($prescription, $actorId) {
            $userId = \App\Models\User::withoutGlobalScopes()
                ->where('patient_id', $prescription->patient_id)
                ->value('id');

            if ($userId) {
                \App\Support\Notify::toUser(
                    (string) $userId,
                    'prescription_issued',
                    'prescription',
                    [
                        'title' => __('prescriber.notify_issued_title'),
                        'body'  => __('prescriber.notify_issued_body', ['reference' => (string) $prescription->reference]),
                        'url'   => route('portals.patient.prescriptions'),
                    ],
                    'normal',
                    $actorId,
                    'Prescription',
                    (string) $prescription->getKey(),
                );
            }
        }, null, false);

        return $prescription->load('items');
    }

    /**
     * Record a hand-over against a prescription.
     *
     * A hand-over is recorded ON THE LINES — `prescription_items.status` is the
     * only place that knows which drug actually left the shelf. The header is
     * then DERIVED from them (Prescription::dispensingStatusFromItems), never
     * asserted: every fillable line handed over reads 'dispensed', some but not
     * all reads 'partially_dispensed'. Before this, the header was hard-set to
     * 'dispensed' on every call, so `partially_dispensed` — a status the schema
     * allows and six readers check for — could never be written at all.
     *
     * Refuses a second dispense of a FULLY dispensed script outright: a repeat
     * POST from the pharmacy queue must not re-stamp a completed hand-over. A
     * partially dispensed one is still open and may be completed later.
     *
     * Options:
     *   notes     — written onto each line this call hands over.
     *   item_ids  — the lines actually handed over. Omit (or null) to hand over
     *               every outstanding line, which is what the pharmacy portal
     *               and the walk-in counter do; pass a list to record a partial
     *               hand-over. Ids that are already dispensed, cancelled or not
     *               on this prescription are ignored.
     *
     * @throws \LogicException when the prescription is not dispensable, or when
     *                         the call would hand over nothing at all.
     */
    public function dispense(Prescription $prescription, ?string $actorId = null, array $options = []): Prescription
    {
        if ($prescription->status === 'dispensed') {
            throw new \LogicException('PRESCRIPTION_ALREADY_DISPENSED');
        }

        if (! $prescription->isDispensable()) {
            throw new \LogicException('PRESCRIPTION_NOT_DISPENSABLE');
        }

        $only = isset($options['item_ids']) && $options['item_ids'] !== null
            ? array_map('strval', (array) $options['item_ids'])
            : null;

        $handedOver = 0;
        $derived    = null;

        DB::transaction(function () use ($prescription, $actorId, $options, $only, &$handedOver, &$derived) {
            $lines = $prescription->items()->get();

            foreach ($lines as $item) {
                // Already handed over, or cancelled and never going to be.
                if (! $item->isOutstanding()) {
                    continue;
                }
                // A partial hand-over: only the lines the pharmacist listed.
                if ($only !== null && ! in_array((string) $item->id, $only, true)) {
                    continue;
                }

                $item->status         = 'dispensed';
                $item->dispensed_at   = now();
                $item->dispense_notes = $options['notes'] ?? $item->dispense_notes;
                $item->save();
                $handedOver++;
            }

            $derived = $prescription->dispensingStatusFromItems($lines);

            // Nothing left the shelf and there is nothing to put right either:
            // a no-op, not a dispense. Say so, rather than returning an
            // untouched script as though the counter had done something. The
            // exception is a header that disagrees with its own lines — a row
            // written before the header was derived — which is corrected here
            // exactly as the repair migration would.
            if ($handedOver === 0 && ($derived === null || $derived === $prescription->status)) {
                throw new \LogicException('PRESCRIPTION_NO_ITEMS_TO_DISPENSE');
            }

            if ($derived !== null) {
                $prescription->status = $derived;
            }

            // Who recorded the hand-over — stamped on a partial too, so the
            // record names the pharmacist behind the counter either way. WHEN
            // it was dispensed is only true once nothing is outstanding, and is
            // never re-stamped: the first completion is the one that counts.
            $prescription->dispensed_by = $actorId ?? $this->ctx->actorId();

            if ($prescription->status === 'dispensed' && $prescription->dispensed_at === null) {
                $prescription->dispensed_at = now();
            }

            $prescription->save();
        });

        $partial = $derived === 'partially_dispensed';

        $this->audit(
            $partial ? 'prescription_partially_dispensed' : 'prescription_dispensed',
            $prescription,
            $actorId,
            ['reason' => $partial
                ? 'Prescription partially dispensed — ' . $handedOver . ' item(s) handed over'
                : 'Prescription dispensed'],
        );

        return $prescription->refresh();
    }

    /**
     * Void a prescription with a documented reason. The record is preserved in
     * full — only its status and the reason are written.
     */
    public function void(Prescription $prescription, string $reason, ?string $actorId = null): Prescription
    {
        return $this->terminate($prescription, 'voided', $reason, $actorId, 'prescription_voided');
    }

    /**
     * Mark a prescription entered-in-error — the FHIR-aligned way of saying it
     * should never have existed, without deleting the evidence that it did.
     */
    public function markEnteredInError(Prescription $prescription, string $reason, ?string $actorId = null): Prescription
    {
        return $this->terminate($prescription, 'entered_in_error', $reason, $actorId, 'prescription_entered_in_error');
    }

    /**
     * Amend a prescription: the original is closed as 'amended' and a NEW
     * prescription is issued carrying `amends_prescription_id` back to it —
     * the same provenance chain ConsultationService uses for clinical notes.
     */
    public function amend(Prescription $original, array $data, string $reason, ?string $actorId = null): Prescription
    {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('PRESCRIPTION_AMENDMENT_REASON_REQUIRED');
        }

        if ($original->isTerminal()) {
            throw new \LogicException('PRESCRIPTION_NOT_AMENDABLE');
        }

        $amendment = DB::transaction(function () use ($original, $data, $reason, $actorId) {
            $original->status      = 'amended';
            $original->void_reason = $reason;
            $original->save();

            return $this->issue(array_merge([
                'patient_id'    => $original->patient_id,
                'facility_id'   => $original->facility_id,
                'visit_id'      => $original->visit_id,
                'prescribed_by' => $actorId ?? $original->prescribed_by,
                'notes'         => $original->notes,
            ], $data, [
                'amends_prescription_id' => $original->id,
            ]), $actorId);
        });

        $this->audit('prescription_amended', $amendment, $actorId, [
            'reason' => $reason,
        ]);

        return $amendment;
    }

    // ──────────────────────────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────────────────────────

    private function terminate(
        Prescription $prescription,
        string $status,
        string $reason,
        ?string $actorId,
        string $auditAction,
    ): Prescription {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('PRESCRIPTION_REASON_REQUIRED');
        }

        if ($prescription->isTerminal()) {
            throw new \LogicException('PRESCRIPTION_ALREADY_' . strtoupper($prescription->status));
        }

        $prescription->status      = $status;
        $prescription->voided_at   = now();
        $prescription->voided_by   = $actorId ?? $this->ctx->actorId();
        $prescription->void_reason = $reason;
        $prescription->save();

        $this->audit($auditAction, $prescription, $actorId, ['reason' => $reason]);

        return $prescription->refresh();
    }

    /**
     * Normalise one item line.
     *
     * Accepts both the portal's field names and the API's historical aliases
     * (`dosage`, `duration`), and resolves `medicine_id` against the national
     * catalogue so the drug a clinician prescribes is the same row the pharmacy
     * stock listing and the patient medicine finder read.
     *
     * @return array<string,mixed>
     */
    private function normaliseItem(array $item): array
    {
        $medicine = null;
        if (! empty($item['medicine_id'])) {
            // Fail loudly: prescribing against an id that is not in the
            // catalogue would hand the pharmacy an unmatchable line.
            $medicine = Medicine::findOrFail($item['medicine_id']);
        }

        $dose = $item['dose'] ?? $item['dosage'] ?? $medicine?->strength;

        $duration = $item['duration_days'] ?? $item['duration'] ?? null;
        if (is_string($duration)) {
            $duration = preg_match('/\d+/', $duration, $m) ? (int) $m[0] : null;
        }

        return [
            'medicine_id'   => $medicine?->id,
            'drug_name'     => $item['drug_name'] ?? $medicine?->name,
            'drug_code'     => $item['drug_code'] ?? $medicine?->atc_code,
            'strength'      => $item['strength'] ?? $medicine?->strength,
            'dosage_form'   => $item['dosage_form'] ?? $item['form'] ?? $medicine?->form,
            'dose'          => $dose,
            'frequency'     => $item['frequency'] ?? null,
            'route'         => $item['route'] ?? null,
            'duration_days' => $duration !== null ? (int) $duration : null,
            'quantity'      => isset($item['quantity']) ? (int) $item['quantity'] : null,
        ];
    }

    private function resolveExpiry(array $data): \Illuminate\Support\Carbon
    {
        if (! empty($data['expires_at'])) {
            return \Illuminate\Support\Carbon::parse($data['expires_at']);
        }

        $days = (int) ($data['validity_days'] ?? self::DEFAULT_VALIDITY_DAYS);

        return now()->addDays($days > 0 ? $days : self::DEFAULT_VALIDITY_DAYS);
    }

    /**
     * Every patient-data write is audited through the same helper the patient
     * portal uses (PortalContextService::auditPatientAccess), so portal and API
     * writes land in one audit trail with one shape.
     *
     * Portal callers get actor and facility from the session; API callers pass
     * their actor explicitly and the facility comes off the prescription, which
     * was itself set from the middleware-resolved facility_id.
     */
    private function audit(string $actionType, Prescription $prescription, ?string $actorId, array $extra = []): void
    {
        $overrides = array_filter([
            'actor_id'    => $actorId ?? $this->ctx->actorId(),
            'facility_id' => $prescription->facility_id,
        ], static fn ($value) => $value !== null);

        $this->ctx->auditPatientAccess(
            actionType:   $actionType,
            resourceType: 'Prescription',
            resourceId:   $prescription->id,
            patientId:    $prescription->patient_id,
            extra:        array_merge($overrides, $extra),
        );
    }
}
