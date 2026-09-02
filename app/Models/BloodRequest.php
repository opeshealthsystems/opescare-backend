<?php

namespace App\Models;

use App\Enums\BloodComponentType;
use App\Enums\BloodGroup;
use App\Enums\BloodRequestStatus;
use App\Enums\BloodRequestUrgency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A patient's request to reserve blood units at a facility.
 *
 * The Blood Finder counterpart of MedicineReservation: `patient_id` is a
 * `patients.id` (the value the mobile auth middleware puts on the request) and
 * `care_facility_id` is a public-directory listing, the same table
 * `blood_availability.facility_id` points at.
 *
 * No payment is taken here, and no unit is dispensed or cross-matched — the
 * blood bank confirms and issues at the counter.
 */
class BloodRequest extends Model
{
    use HasUuids;

    protected $table = 'blood_requests';

    protected $fillable = [
        'reference',
        'patient_id',
        'care_facility_id',
        'blood_availability_id',
        'blood_group',
        'component_type',
        'quantity',
        'urgency',
        'status',
        'contact_phone',
        'patient_note',
        'facility_note',
        'decided_by',
        'cancelled_reason',
        'needed_by',
        'expires_at',
        'confirmed_at',
        'decided_at',
        'fulfilled_at',
        'cancelled_at',
    ];

    protected $casts = [
        'blood_group'    => BloodGroup::class,
        'component_type' => BloodComponentType::class,
        'urgency'        => BloodRequestUrgency::class,
        'status'         => BloodRequestStatus::class,
        'quantity'       => 'integer',
        'needed_by'      => 'datetime',
        'expires_at'     => 'datetime',
        'confirmed_at'   => 'datetime',
        'decided_at'     => 'datetime',
        'fulfilled_at'   => 'datetime',
        'cancelled_at'   => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function careFacility(): BelongsTo
    {
        return $this->belongsTo(CareFacility::class, 'care_facility_id');
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(BloodAvailability::class, 'blood_availability_id');
    }

    /** Requests still counting against the patient's open-request limit. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            static fn (BloodRequestStatus $s) => $s->value,
            array_filter(
                BloodRequestStatus::cases(),
                static fn (BloodRequestStatus $s) => $s->isOpen(),
            ),
        ));
    }
}
