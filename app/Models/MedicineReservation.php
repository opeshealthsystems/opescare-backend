<?php

namespace App\Models;

use App\Enums\MedicineReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A patient's hold on a medicine at a specific pharmacy.
 *
 * Distinct from the CareMap-era `MedicineReservationRequest`, which keys
 * `patient_id` to `users` and identifies medicines by free text. This model
 * is the Medicine Finder's own record: `patient_id` is a `patients.id` (the
 * value the mobile auth middleware puts on the request), the medicine is a
 * catalog FK, and the price is captured at reservation time so a later
 * pharmacy price change cannot silently rewrite what the patient was quoted.
 *
 * No payment is taken here — Mobile Money capture is a deliberate fast-follow.
 */
class MedicineReservation extends Model
{
    use HasUuids;

    protected $table = 'medicine_reservations';

    protected $fillable = [
        'reference',
        'patient_id',
        'medicine_id',
        'care_facility_id',
        'stock_id',
        'prescription_id',
        'quantity',
        'pack_size',
        'unit_price',
        'total_price',
        'currency',
        'status',
        'patient_note',
        'pharmacy_note',
        'cancelled_reason',
        'expires_at',
        'confirmed_at',
        'collected_at',
        'cancelled_at',
    ];

    protected $casts = [
        'status'       => MedicineReservationStatus::class,
        'quantity'     => 'integer',
        'unit_price'   => 'float',
        'total_price'  => 'float',
        'expires_at'   => 'datetime',
        'confirmed_at' => 'datetime',
        'collected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function careFacility(): BelongsTo
    {
        return $this->belongsTo(CareFacility::class, 'care_facility_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(MedicinePharmacyStock::class, 'stock_id');
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    /** Reservations still counting against the patient's open-hold limit. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            static fn (MedicineReservationStatus $s) => $s->value,
            array_filter(
                MedicineReservationStatus::cases(),
                static fn (MedicineReservationStatus $s) => $s->isOpen(),
            ),
        ));
    }
}
