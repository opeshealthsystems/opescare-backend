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
 *
 * ── Two vocabularies, one row ─────────────────────────────────────────────
 *
 * The `status` column is cast through MedicineReservationStatus, and every
 * patient-side read (MobilePharmacyController::reservationPayload) calls
 * ->value / ->label() / ->isOpen() on it. Adding a value the enum does not
 * know would make those reads throw, so the pharmacy desk's vocabulary
 * (pending → accepted → ready → collected, ready → dispatched → delivered)
 * is MAPPED onto the legacy values rather than written over them:
 *
 *   desk       column `status`       column `delivery_status`
 *   pending    pending               —
 *   accepted   confirmed             —
 *   ready      ready_for_pickup      null
 *   dispatched ready_for_pickup      dispatched      (fulfilment_mode = delivery)
 *   delivered  collected             delivered       (fulfilment_mode = delivery)
 *   collected  collected             null            (fulfilment_mode = pickup)
 *   cancelled  cancelled | rejected | expired
 *
 * deskStatus() is the read side of that table and scopeDeskStatus() the query
 * side; the STATUS_* constants hold the legacy column values so a writer
 * never spells them by hand.
 */
class MedicineReservation extends Model
{
    use HasUuids;

    protected $table = 'medicine_reservations';

    // ── Column values (`status`) — the legacy enum's spellings ────────────
    public const STATUS_PENDING   = 'pending';
    public const STATUS_ACCEPTED  = 'confirmed';
    public const STATUS_READY     = 'ready_for_pickup';
    public const STATUS_COLLECTED = 'collected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_EXPIRED   = 'expired';

    // ── Column values (`fulfilment_mode`) ─────────────────────────────────
    public const FULFILMENT_PICKUP   = 'pickup';
    public const FULFILMENT_DELIVERY = 'delivery';

    /** @var list<string> */
    public const FULFILMENT_MODES = [self::FULFILMENT_PICKUP, self::FULFILMENT_DELIVERY];

    // ── Column values (`delivery_status`) ─────────────────────────────────
    public const DELIVERY_DISPATCHED = 'dispatched';
    public const DELIVERY_DELIVERED  = 'delivered';

    // ── The desk vocabulary — what the pharmacy API speaks ────────────────
    public const DESK_PENDING    = 'pending';
    public const DESK_ACCEPTED   = 'accepted';
    public const DESK_READY      = 'ready';
    public const DESK_COLLECTED  = 'collected';
    public const DESK_DISPATCHED = 'dispatched';
    public const DESK_DELIVERED  = 'delivered';
    public const DESK_CANCELLED  = 'cancelled';

    /** @var list<string> every value `?status=` accepts */
    public const DESK_STATUSES = [
        self::DESK_PENDING, self::DESK_ACCEPTED, self::DESK_READY, self::DESK_COLLECTED,
        self::DESK_DISPATCHED, self::DESK_DELIVERED, self::DESK_CANCELLED,
    ];

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
        'fulfilment_mode',
        'delivery_address',
        'delivery_phone',
        'delivery_status',
        'patient_note',
        'pharmacy_note',
        'cancelled_reason',
        'expires_at',
        'confirmed_at',
        'accepted_at',
        'ready_at',
        'dispatched_at',
        'delivered_at',
        'collected_at',
        'cancelled_at',
    ];

    protected $casts = [
        'status'        => MedicineReservationStatus::class,
        'quantity'      => 'integer',
        'unit_price'    => 'float',
        'total_price'   => 'float',
        'expires_at'    => 'datetime',
        'confirmed_at'  => 'datetime',
        'accepted_at'   => 'datetime',
        'ready_at'      => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at'  => 'datetime',
        'collected_at'  => 'datetime',
        'cancelled_at'  => 'datetime',
    ];

    protected $attributes = [
        'fulfilment_mode' => self::FULFILMENT_PICKUP,
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

    public function isDelivery(): bool
    {
        return $this->fulfilment_mode === self::FULFILMENT_DELIVERY;
    }

    /**
     * The desk's word for where this reservation is — see the class docblock
     * for the mapping. Never null: an unknown legacy value reads as cancelled,
     * which is the only safe thing to tell a counter about a hold it cannot
     * place.
     */
    public function deskStatus(): string
    {
        $status = $this->status instanceof MedicineReservationStatus
            ? $this->status->value
            : (string) $this->status;

        if ($this->isDelivery()) {
            if ($this->delivery_status === self::DELIVERY_DELIVERED) {
                return self::DESK_DELIVERED;
            }
            if ($this->delivery_status === self::DELIVERY_DISPATCHED && $status === self::STATUS_READY) {
                return self::DESK_DISPATCHED;
            }
        }

        return match ($status) {
            self::STATUS_PENDING   => self::DESK_PENDING,
            self::STATUS_ACCEPTED  => self::DESK_ACCEPTED,
            self::STATUS_READY     => self::DESK_READY,
            self::STATUS_COLLECTED => self::DESK_COLLECTED,
            default                => self::DESK_CANCELLED,
        };
    }

    /**
     * Rows whose deskStatus() is $desk — the query-side twin of that method,
     * so a list filter and a row's own label can never disagree.
     */
    public function scopeDeskStatus(Builder $query, string $desk): Builder
    {
        return match ($desk) {
            self::DESK_PENDING  => $query->where('status', self::STATUS_PENDING),
            self::DESK_ACCEPTED => $query->where('status', self::STATUS_ACCEPTED),
            self::DESK_READY    => $query->where('status', self::STATUS_READY)
                ->where(fn (Builder $q) => $q->whereNull('delivery_status')
                    ->orWhere('fulfilment_mode', '!=', self::FULFILMENT_DELIVERY)),
            self::DESK_DISPATCHED => $query->where('status', self::STATUS_READY)
                ->where('fulfilment_mode', self::FULFILMENT_DELIVERY)
                ->where('delivery_status', self::DELIVERY_DISPATCHED),
            self::DESK_DELIVERED => $query->where('status', self::STATUS_COLLECTED)
                ->where('fulfilment_mode', self::FULFILMENT_DELIVERY)
                ->where('delivery_status', self::DELIVERY_DELIVERED),
            self::DESK_COLLECTED => $query->where('status', self::STATUS_COLLECTED)
                ->where(fn (Builder $q) => $q->whereNull('delivery_status')
                    ->orWhere('fulfilment_mode', '!=', self::FULFILMENT_DELIVERY)),
            self::DESK_CANCELLED => $query->whereIn('status', [
                self::STATUS_CANCELLED, self::STATUS_REJECTED, self::STATUS_EXPIRED,
            ]),
            // An unknown word matches nothing rather than everything.
            default => $query->whereRaw('1 = 0'),
        };
    }

    /** Deliveries the desk still has to move: accepted, being prepared, or on the road. */
    public function scopeOpenDeliveries(Builder $query): Builder
    {
        return $query->where('fulfilment_mode', self::FULFILMENT_DELIVERY)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_ACCEPTED, self::STATUS_READY]);
    }
}
