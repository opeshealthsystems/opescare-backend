<?php

namespace App\Http\Resources\PharmacyDesk;

use App\Http\Resources\ApiResource;
use App\Models\MedicineReservation;
use Illuminate\Http\Request;

/**
 * A medicine reservation as the pharmacy desk sees it.
 *
 *   { id, reference, status, fulfilment_mode, delivery_address, delivery_phone,
 *     delivery_status, quantity, total_price, currency, expires_at, created_at,
 *     medicine: {id, name, strength, form}, patient: {display_name, health_id} }
 *
 * `status` is the DESK vocabulary (pending | accepted | ready | collected |
 * dispatched | delivered | cancelled) — MedicineReservation::deskStatus() —
 * not the column's legacy spelling. The patient side keeps reading the
 * column through its enum; this resource is the only place the two meet.
 *
 * The patient is named short ("Nadia F.") on purpose: a reservation list is
 * a bench view many people glance at, and the Health ID is what actually
 * identifies the person at the counter.
 */
class PharmacyDeskReservationResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var MedicineReservation $r */
        $r = $this->resource;

        $patient  = $r->relationLoaded('patient') ? $r->patient : null;
        $medicine = $r->relationLoaded('medicine') ? $r->medicine : null;

        return [
            'id'               => $r->id,
            'reference'        => $r->reference,
            'status'           => $r->deskStatus(),
            'fulfilment_mode'  => $r->fulfilment_mode ?: MedicineReservation::FULFILMENT_PICKUP,
            'delivery_address' => $r->delivery_address,
            'delivery_phone'   => $r->delivery_phone,
            'delivery_status'  => $r->delivery_status,
            'quantity'         => $r->quantity,
            'pack_size'        => $r->pack_size,
            'unit_price'       => $r->unit_price,
            'total_price'      => $r->total_price,
            'currency'         => $r->currency,
            'prescription_id'  => $r->prescription_id,
            'patient_note'     => $r->patient_note,
            'pharmacy_note'    => $r->pharmacy_note,
            'cancelled_reason' => $r->cancelled_reason,
            'expires_at'       => $r->expires_at?->toIso8601String(),
            'created_at'       => $r->created_at?->toIso8601String(),
            'accepted_at'      => $r->accepted_at?->toIso8601String(),
            'ready_at'         => $r->ready_at?->toIso8601String(),
            'dispatched_at'    => $r->dispatched_at?->toIso8601String(),
            'delivered_at'     => $r->delivered_at?->toIso8601String(),
            'collected_at'     => $r->collected_at?->toIso8601String(),
            'medicine'         => $medicine === null ? null : [
                'id'       => $medicine->id,
                'name'     => $medicine->name,
                'strength' => $medicine->strength,
                'form'     => $medicine->form,
            ],
            'patient'          => [
                'display_name' => $patient ? self::shortName($patient->first_name, $patient->last_name) : null,
                'health_id'    => $patient?->health_id,
            ],
        ];
    }

    /** "Nadia Finder" → "Nadia F." */
    public static function shortName(?string $first, ?string $last): string
    {
        $first = trim((string) $first);
        $last  = trim((string) $last);

        if ($last === '') {
            return $first;
        }

        return trim($first . ' ' . mb_strtoupper(mb_substr($last, 0, 1)) . '.');
    }
}
