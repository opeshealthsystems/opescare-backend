<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A prescription is a clinical event, and clinical events are immutable here.
 *
 * It is never rewritten and never deleted. A mistake is corrected by voiding it
 * or marking it entered-in-error with a documented reason; a change of therapy
 * is an amendment — a NEW prescription linked back to the original through
 * `amends_prescription_id`, exactly the way ConsultationService::amendClinicalNote()
 * chains clinical notes.
 *
 * The guards below are enforced in the model rather than in a service so that no
 * caller — portal, API, console or a future one — can route around them.
 */
class Prescription extends Model
{
    use HasFactory, HasUuids;
    use \App\Traits\HasFacilityScope;

    /**
     * Attributes fixed at the moment of prescribing. Changing any of them would
     * silently rewrite what a clinician ordered, for whom, and when.
     */
    public const IMMUTABLE_ATTRIBUTES = [
        'patient_id',
        'facility_id',
        'visit_id',
        'prescribed_by',
        'prescribed_at',
        'amends_prescription_id',
    ];

    /** Statuses from which no further clinical transition is allowed. */
    public const TERMINAL_STATUSES = [
        'dispensed',
        'cancelled',
        'voided',
        'entered_in_error',
        'amended',
    ];

    protected $fillable = [
        'patient_id',
        'facility_id',
        'visit_id',
        'prescribed_by',
        'status',
        'notes',
        'dispensing_pharmacy_name',
        'dispensing_pharmacy_address',
        'dispensing_pharmacy_phone',
        'dispensing_pharmacy_fax',
        'pharmacy_routing_status',
        'pharmacy_routing_sent_at',
        'pharmacy_confirmed_at',
        'prescribed_at',
        'dispensed_at',
        'dispensed_by',
        'expires_at',
        'pharmacy_route_id',
        'amends_prescription_id',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'prescribed_at'            => 'datetime',
        'dispensed_at'             => 'datetime',
        'expires_at'               => 'datetime',
        'voided_at'                => 'datetime',
        'pharmacy_routing_sent_at' => 'datetime',
        'pharmacy_confirmed_at'    => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $prescription): void {
            foreach (self::IMMUTABLE_ATTRIBUTES as $attribute) {
                if ($prescription->isDirty($attribute)) {
                    throw new \LogicException(
                        "Prescription is immutable: '{$attribute}' cannot be changed after it is issued. "
                        . 'Amend the prescription or mark it entered-in-error instead.'
                    );
                }
            }

            // A prescription that has reached a terminal state may not be walked
            // back into an active one — that would silently un-dispense or
            // resurrect a voided clinical order.
            if ($prescription->isDirty('status')) {
                $previous = (string) $prescription->getOriginal('status');
                if (in_array($previous, self::TERMINAL_STATUSES, true)) {
                    throw new \LogicException(
                        "Prescription is already '{$previous}' and cannot transition to "
                        . "'{$prescription->status}'. Issue a new prescription instead."
                    );
                }
            }
        });

        static::deleting(function (self $prescription): void {
            throw new \LogicException(
                'A prescription is an immutable clinical event and cannot be deleted. '
                . 'Void it or mark it entered-in-error instead.'
            );
        });
    }

    /**
     * Hard-deleting a clinical event is never legitimate. Overridden as well as
     * hooked so the refusal is identical whether the caller uses delete() or
     * triggers the model event.
     */
    public function delete(): bool
    {
        throw new \LogicException(
            'A prescription is an immutable clinical event and cannot be deleted. '
            . 'Void it or mark it entered-in-error instead.'
        );
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /** The clinician who issued it. */
    public function prescriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescribed_by');
    }

    /** The prescription this one supersedes, if it is an amendment. */
    public function amends(): BelongsTo
    {
        return $this->belongsTo(self::class, 'amends_prescription_id');
    }

    /** The amendment that superseded this prescription, if any. */
    public function amendedBy(): HasMany
    {
        return $this->hasMany(self::class, 'amends_prescription_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Has this prescription reached a state that admits no further transition? */
    public function isTerminal(): bool
    {
        return in_array((string) $this->status, self::TERMINAL_STATUSES, true);
    }

    /** Can a pharmacy still dispense against it? */
    public function isDispensable(): bool
    {
        if ($this->isTerminal() || $this->status === 'expired') {
            return false;
        }

        return ! ($this->expires_at && $this->expires_at->isPast());
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'dispensed'            => 'success',
            'active'               => 'info',
            'partially_dispensed'  => 'warning',
            'voided',
            'entered_in_error'     => 'danger',
            'amended'              => 'warning',
            'expired'              => 'default',
            'cancelled'            => 'default',
            default                => 'info',
        };
    }
}
