<?php

namespace App\Modules\CareMap\Services;

use App\Enums\BloodComponentType;
use App\Enums\BloodGroup;
use App\Enums\BloodRequestStatus;
use App\Enums\BloodRequestUrgency;
use App\Models\BloodAvailability;
use App\Models\BloodRequest;
use App\Models\CareFacility;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates, answers and cancels patient blood-unit requests.
 *
 * Mirrors App\Modules\Pharmacy\Services\MedicineReservationService: a request
 * is a *hold of intent* the patient shows at the counter. It takes no payment,
 * dispenses nothing, and performs no cross-match — the blood bank confirms and
 * issues. Statuses are append-forward and nothing here ever deletes a row.
 *
 * Three actors move a request, and only these three:
 *   - the patient      → cancel()
 *   - the scheduler    → expireLapsed()   (blood:expire-requests, hourly)
 *   - the blood bank   → decide()          (the facility-side receiver)
 *
 * Availability is read from `blood_availability` (the same table
 * BloodAvailabilitySearchService queries) — this service never writes to it.
 * For a facility with an operational blood-bank record that table is itself a
 * projection of `blood_inventories`, maintained by
 * App\Modules\CareMap\Services\BloodAvailabilityProjector, so the gate below
 * tests the same number the staff portal shows. Decrementing stock stays the
 * facility's own act, through its own surface.
 */
class BloodRequestService
{
    /** How long a facility has to act on a request before it lapses. */
    public const HOLD_HOURS = 24;

    /** Guard against one patient blanket-reserving a blood bank's shelf. */
    public const MAX_OPEN_REQUESTS = 5;
    public const MAX_UNITS = 6;

    /** Failure reasons the controller maps onto HTTP responses. */
    public const ERR_NOT_AVAILABLE   = 'BLOOD_NOT_AVAILABLE';
    public const ERR_TOO_MANY_OPEN   = 'TOO_MANY_OPEN_REQUESTS';
    public const ERR_DUPLICATE       = 'REQUEST_ALREADY_OPEN';
    public const ERR_NOT_CANCELLABLE = 'REQUEST_NOT_CANCELLABLE';
    public const ERR_BAD_TRANSITION  = 'REQUEST_TRANSITION_NOT_ALLOWED';

    public function request(
        string $patientId,
        CareFacility $facility,
        BloodGroup $bloodGroup,
        BloodComponentType $componentType,
        int $quantity,
        BloodRequestUrgency $urgency = BloodRequestUrgency::Routine,
        ?string $contactPhone = null,
        ?string $patientNote = null,
        ?string $neededBy = null,
    ): BloodRequest {
        $quantity = max(1, min($quantity, self::MAX_UNITS));

        /*
         * The facility must actually report this group/component as available —
         * otherwise the patient would travel for nothing.
         *
         * `reportedByRealSource()` is the same scope the public Blood Finder
         * reads through, and applying it here is the point: without it a row
         * that is withheld from search because it is seeded or unattributed
         * would still accept a booking. A patient cannot see that stock, but
         * could be told a request against it succeeded — which for someone
         * chasing blood in an emergency is worse than being told there is none.
         * What can be ordered and what can be found must be the same set.
         */
        $availability = BloodAvailability::query()
            ->reportedByRealSource()
            ->where('facility_id', $facility->id)
            ->where('blood_group', $bloodGroup->value)
            ->where('component_type', $componentType->value)
            ->where('availability_status', 'available')
            ->first();

        if (! $availability) {
            throw new RuntimeException(self::ERR_NOT_AVAILABLE);
        }

        return DB::transaction(function () use (
            $patientId, $facility, $availability, $bloodGroup, $componentType,
            $quantity, $urgency, $contactPhone, $patientNote, $neededBy
        ) {
            // Retire this patient's lapsed holds before counting the quota.
            //
            // `expires_at` is written on every request but nothing used to read
            // it, so a request the blood bank never acted on stayed `pending`
            // for ever and kept counting against MAX_OPEN_REQUESTS. Five
            // unanswered requests locked the patient out of the feature
            // permanently — the first real user session bricked itself.
            //
            // The scheduled sweep (blood:expire-requests) is the system-wide
            // pass; this is the same rule applied inline so the quota is correct
            // the instant it is read, even on a host where the scheduler is not
            // running.
            $this->expireLapsed($patientId);

            $openForPatient = BloodRequest::query()
                ->where('patient_id', $patientId)
                ->open()
                ->lockForUpdate()
                ->get();

            if ($openForPatient->count() >= self::MAX_OPEN_REQUESTS) {
                throw new RuntimeException(self::ERR_TOO_MANY_OPEN);
            }

            $duplicate = $openForPatient
                ->where('care_facility_id', $facility->id)
                ->first(fn (BloodRequest $r) => $r->blood_group === $bloodGroup
                    && $r->component_type === $componentType);

            if ($duplicate !== null) {
                throw new RuntimeException(self::ERR_DUPLICATE);
            }

            return BloodRequest::create([
                'reference'             => $this->generateReference(),
                'patient_id'            => $patientId,
                'care_facility_id'      => $facility->id,
                'blood_availability_id' => $availability->id,
                'blood_group'           => $bloodGroup->value,
                'component_type'        => $componentType->value,
                'quantity'              => $quantity,
                'urgency'               => $urgency->value,
                'status'                => BloodRequestStatus::Pending->value,
                'contact_phone'         => $contactPhone,
                'patient_note'          => $patientNote,
                'needed_by'             => $neededBy,
                'expires_at'            => now()->addHours(self::HOLD_HOURS),
            ]);
        });
    }

    /**
     * Lapses open requests whose hold window has run out.
     *
     * A request is a 24-hour hold (HOLD_HOURS). BloodRequestStatus documents
     * `pending|confirmed|ready → expired (scheduler)`, but no scheduler existed:
     * `expires_at` was written and never read, so every request stayed open for
     * ever and MAX_OPEN_REQUESTS turned into a permanent lockout after five
     * unanswered holds.
     *
     * Never deletes and never touches a terminal row — expiry is one more
     * forward-only transition, so the facility's history stays intact.
     *
     * @param  string|null  $patientId  Restrict to one patient; null sweeps all.
     * @return int  Number of requests moved to `expired`.
     */
    public function expireLapsed(?string $patientId = null): int
    {
        $query = BloodRequest::query()
            ->open()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());

        if ($patientId !== null) {
            $query->where('patient_id', $patientId);
        }

        return $query->update([
            'status'     => BloodRequestStatus::Expired->value,
            'updated_at' => now(),
        ]);
    }

    /**
     * The blood bank's answer — the missing receiver.
     *
     * A patient could raise a request and nothing on the platform could act on
     * it: `confirmed`, `ready`, `fulfilled` and `rejected` were unreachable, so
     * a request only ever left `pending` by the patient cancelling or the
     * hourly sweep retiring it. This is the one write that moves it.
     *
     * Deliberately not a workflow engine — a status transition, an actor and a
     * timestamp. The legal moves live in
     * App\Enums\BloodRequestStatus::facilityTransitions() and are forward-only;
     * a terminal request is never reopened and NOTHING here deletes a row, the
     * same rule expireLapsed() follows.
     *
     * Stock is not decremented: issuing a unit is the facility's own act
     * through its own surface (BloodInventoryService), and that write
     * re-publishes availability on its own.
     *
     * @param  string  $actor  The integration client id from middleware
     *                         attributes — never a caller-supplied value.
     *
     * @throws RuntimeException  self::ERR_BAD_TRANSITION
     */
    public function decide(
        BloodRequest $request,
        BloodRequestStatus $target,
        string $actor,
        ?string $facilityNote = null,
    ): BloodRequest {
        if (! $request->status->canTransitionTo($target)) {
            throw new RuntimeException(self::ERR_BAD_TRANSITION);
        }

        $now = now();

        $attributes = [
            'status'     => $target->value,
            'decided_by' => $actor,
            'decided_at' => $now,
        ];

        if ($facilityNote !== null) {
            $attributes['facility_note'] = $facilityNote;
        }

        // The dedicated columns are stamped once, the first time the request
        // reaches that point — a `ready` after a `confirmed` must not rewrite
        // when the bank confirmed.
        if ($target === BloodRequestStatus::Confirmed && $request->confirmed_at === null) {
            $attributes['confirmed_at'] = $now;
        }

        if ($target === BloodRequestStatus::Ready && $request->confirmed_at === null) {
            // Straight from pending to ready — the bank confirmed implicitly.
            $attributes['confirmed_at'] = $now;
        }

        if ($target === BloodRequestStatus::Fulfilled) {
            $attributes['fulfilled_at'] = $now;
        }

        $request->update($attributes);

        return $request->refresh();
    }

    /**
     * Patient-initiated cancellation. Never deletes the row — the request moves
     * to a terminal status so the facility's history stays intact.
     */
    public function cancel(BloodRequest $request, ?string $reason = null): BloodRequest
    {
        if (! $request->status->isCancellableByPatient()) {
            throw new RuntimeException(self::ERR_NOT_CANCELLABLE);
        }

        $request->update([
            'status'           => BloodRequestStatus::Cancelled->value,
            'cancelled_at'     => now(),
            'cancelled_reason' => $reason,
        ]);

        return $request->refresh();
    }

    /**
     * Counter-facing reference. Ambiguous glyphs (0/O, 1/I) are excluded so a
     * blood-bank clerk reading it off a patient's phone cannot mistype it.
     */
    private function generateReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = 'OC-BL-' . $code;
        } while (BloodRequest::where('reference', $reference)->exists());

        return $reference;
    }
}
