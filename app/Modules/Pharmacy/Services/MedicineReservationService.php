<?php

namespace App\Modules\Pharmacy\Services;

use App\Enums\MedicineReservationStatus;
use App\Models\CareFacility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\MedicineReservation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates and cancels patient medicine reservations.
 *
 * A reservation is a *hold* the patient shows at the counter — it takes no
 * payment and dispenses nothing. Mobile Money capture and pharmacy-side
 * confirmation are deliberate fast-follows; the record is designed so adding
 * them later never has to rewrite history (statuses are append-forward, and
 * the quoted price is frozen on the row at creation time).
 */
class MedicineReservationService
{
    /** How long a pharmacy has to act on a hold before it lapses. */
    public const HOLD_HOURS = 24;

    /** Guard against a patient blanket-reserving a pharmacy's whole shelf. */
    public const MAX_OPEN_RESERVATIONS = 10;
    public const MAX_QUANTITY = 10;

    /** Thrown reasons the controller maps to error codes. */
    public const ERR_NOT_RESERVABLE     = 'STOCK_NOT_RESERVABLE';
    public const ERR_TOO_MANY_OPEN      = 'TOO_MANY_OPEN_RESERVATIONS';
    public const ERR_DUPLICATE          = 'RESERVATION_ALREADY_OPEN';
    public const ERR_NOT_CANCELLABLE    = 'RESERVATION_NOT_CANCELLABLE';
    public const ERR_PRESCRIPTION_REQUIRED = 'PRESCRIPTION_REQUIRED';

    /**
     * @param  string|null  $prescriptionId  already verified to belong to this
     *                                       patient by the caller.
     */
    public function reserve(
        string $patientId,
        Medicine $medicine,
        CareFacility $pharmacy,
        int $quantity,
        ?string $packSize = null,
        ?string $patientNote = null,
        ?string $prescriptionId = null,
    ): MedicineReservation {
        $quantity = max(1, min($quantity, self::MAX_QUANTITY));

        // A prescription-only medicine cannot be held without one attached —
        // the pharmacy has nothing to dispense against otherwise.
        if ($medicine->prescription_required && $prescriptionId === null) {
            throw new RuntimeException(self::ERR_PRESCRIPTION_REQUIRED);
        }

        $stock = MedicinePharmacyStock::query()
            ->where('medicine_id', $medicine->id)
            ->where('care_facility_id', $pharmacy->id)
            ->first();

        if (! $stock || ! $stock->isReservable()) {
            throw new RuntimeException(self::ERR_NOT_RESERVABLE);
        }

        return DB::transaction(function () use (
            $patientId, $medicine, $pharmacy, $stock, $quantity, $packSize, $patientNote, $prescriptionId
        ) {
            $openForPatient = MedicineReservation::query()
                ->where('patient_id', $patientId)
                ->open()
                ->lockForUpdate()
                ->get();

            if ($openForPatient->count() >= self::MAX_OPEN_RESERVATIONS) {
                throw new RuntimeException(self::ERR_TOO_MANY_OPEN);
            }

            $duplicate = $openForPatient
                ->where('medicine_id', $medicine->id)
                ->where('care_facility_id', $pharmacy->id)
                ->isNotEmpty();

            if ($duplicate) {
                throw new RuntimeException(self::ERR_DUPLICATE);
            }

            $unitPrice = $stock->unit_price;

            return MedicineReservation::create([
                'reference'        => $this->generateReference(),
                'patient_id'       => $patientId,
                'medicine_id'      => $medicine->id,
                'care_facility_id' => $pharmacy->id,
                'stock_id'         => $stock->id,
                'prescription_id'  => $prescriptionId,
                'quantity'         => $quantity,
                'pack_size'        => $packSize ?? $stock->pack_size ?? $medicine->default_pack_size,
                'unit_price'       => $unitPrice,
                'total_price'      => $unitPrice === null ? null : round($unitPrice * $quantity, 2),
                'currency'         => $stock->currency ?: ($medicine->currency ?: 'XAF'),
                'status'           => MedicineReservationStatus::Pending->value,
                'patient_note'     => $patientNote,
                'expires_at'       => now()->addHours(self::HOLD_HOURS),
            ]);
        });
    }

    /**
     * Patient-initiated cancellation. Never deletes the row — the reservation
     * moves to a terminal status so the pharmacy's history stays intact.
     */
    public function cancel(MedicineReservation $reservation, ?string $reason = null): MedicineReservation
    {
        if (! $reservation->status->isCancellableByPatient()) {
            throw new RuntimeException(self::ERR_NOT_CANCELLABLE);
        }

        $reservation->update([
            'status'           => MedicineReservationStatus::Cancelled->value,
            'cancelled_at'     => now(),
            'cancelled_reason' => $reason,
        ]);

        return $reservation->refresh();
    }

    /**
     * Counter-facing reference. Ambiguous glyphs (0/O, 1/I) are excluded so a
     * pharmacist reading it off a patient's phone cannot mistype it.
     */
    private function generateReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = 'OC-RX-' . $code;
        } while (MedicineReservation::where('reference', $reference)->exists());

        return $reference;
    }
}
