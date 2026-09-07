<?php

namespace App\Services\Payments;

use App\Models\MobileInvoice;
use App\Services\Payments\Domain\Money;
use App\Services\Payments\Domain\PaymentStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Issues invoices. The single writer of `mobile_invoices` rows.
 *
 * ── Why `patient_id` is an argument and not a field ─────────────────────────
 * Every issue path takes the patient as an explicit parameter, and every caller
 * gets it from `$request->attributes` (set by `AuthenticateMobilePatient` from
 * the bearer token). It is absent from `MobileInvoice::$fillable`, so no
 * request body can reach it even by accident: an attacker who posts
 * `{"patient_id": "<someone else>"}` changes nothing.
 *
 * ── Where invoices come from ────────────────────────────────────────────────
 * `issueFor()` is the method the payable modules should call when they land —
 * a booking confirming an appointment, a pharmacy counter dispensing a
 * prescription. Until then `issueSelfService()` is the only issuer, driven by
 * the app, and it stamps `subject_type = 'self_service'` so those rows are
 * distinguishable from real facility billing forever after.
 */
final class InvoiceService
{
    /** How long an unpaid invoice stays open before the sweeper expires it. */
    public const DEFAULT_TTL_HOURS = 48;

    /**
     * Issue an invoice against a payable subject owned by another module.
     *
     * @param  string  $patientId    From `$request->attributes`, never a body.
     * @param  string  $subjectType  'appointment' | 'lab_order' | 'medicine_reservation' | …
     * @param  string|null  $reference  For an issuer that carries its OWN
     *         idempotency key and wants the unique index on `reference` to be
     *         the thing that stops one submit issuing two invoices: pass a
     *         reference derived from that key, and a retry collides on INSERT
     *         instead of racing (see `SubscriptionCheckoutService`). Everyone
     *         else passes null and gets a fresh random one.
     */
    public function issueFor(
        string $patientId,
        Money $amount,
        string $description,
        string $subjectType,
        ?string $subjectId = null,
        ?string $facilityId = null,
        ?int $ttlHours = null,
        bool $isDemo = false,
        ?string $reference = null,
    ): MobileInvoice {
        $invoice = new MobileInvoice([
            'reference'    => $reference ?? $this->mintReference(),
            'facility_id'  => $facilityId,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'description'  => mb_substr($description, 0, 255),
            'amount_minor' => $amount->minor,
            'currency'     => $amount->currency,
            'expires_at'   => Carbon::now()->addHours($ttlHours ?? self::DEFAULT_TTL_HOURS),
        ]);

        // Not fillable, on purpose. Assigned here, from the caller's
        // authenticated context.
        $invoice->patient_id = $patientId;
        $invoice->status = PaymentStatus::Pending;
        $invoice->is_demo = $isDemo;

        $invoice->save();

        return $invoice;
    }

    /**
     * The app-driven path: a patient raising an invoice for themselves.
     *
     * This exists because the Finder's payable modules (booking, dispensing,
     * lab orders) do not issue invoices yet, and a payment surface with no
     * issuer is untestable and unusable. It is patient-scoped and
     * amount-bounded, and every row it writes is stamped `self_service`, so
     * when a real issuer lands these are trivially separable from facility
     * billing — and this method can be deleted without touching anything else.
     */
    public function issueSelfService(
        string $patientId,
        Money $amount,
        string $description,
        ?string $facilityId = null,
        bool $isDemo = false,
    ): MobileInvoice {
        return $this->issueFor(
            patientId: $patientId,
            amount: $amount,
            description: $description,
            subjectType: 'self_service',
            subjectId: null,
            facilityId: $facilityId,
            isDemo: $isDemo,
        );
    }

    /**
     * A human-quotable reference: `OC-2609-7KF3QW`.
     *
     * Crockford-ish alphabet with I, O, 1 and 0 removed — this string gets read
     * down a phone line to a pharmacy counter, and `INV-0O1I` is unreadable.
     * The random tail is 6 characters of 32 (~30 bits); the unique index on
     * `reference` is what actually guarantees uniqueness, and this loop retries
     * rather than trusting the odds.
     */
    private function mintReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $tail = '';
            for ($i = 0; $i < 6; $i++) {
                $tail .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $reference = 'OC-'.Carbon::now()->format('ym').'-'.$tail;

            if (! MobileInvoice::where('reference', $reference)->exists()) {
                return $reference;
            }
        }

        // Vanishingly unlikely; a collision-proof fallback beats a loop that
        // gives up and returns a duplicate.
        return 'OC-'.Carbon::now()->format('ym').'-'.strtoupper(Str::random(10));
    }
}
