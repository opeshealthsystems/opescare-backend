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
        'reference',
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
        // Short human-quotable handle (RX-XXXX-XXXX) — what a pharmacy is told
        // down a phone or reads off a printed script. Set by the migration's
        // deterministic backfill and by PrescriptionService on issue.
        'reference',
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
        // Every prescription gets its human-quotable handle at creation, on every
        // path there is - prescriber desk, mobile prescribing, seeder, console.
        // This used to live only in the migration backfill, so every NEW
        // prescription saved with a NULL reference and was unfindable by the one
        // thing a pharmacy is ever handed: the prescription number.
        static::creating(function (self $prescription): void {
            if (filled($prescription->reference)) {
                return;
            }

            $prescription->reference = self::allocateReference((string) $prescription->getKey());
        });

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

    /** Revocable capabilities to view this prescription (printed or on screen). */
    public function accessTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PrescriptionAccessToken::class);
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

    /**
     * Crockford's alphabet - no I, L, O or U, so a number read down a phone line
     * or copied by hand cannot turn a 1 into an I or a 0 into an O.
     */
    private const REFERENCE_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * A short, unique, human-quotable handle: RX-XXXX-XXXX.
     *
     * Derived from the row's own UUID so it lands on exactly the value the
     * migration's backfill would have produced - one prescription can never be
     * numbered two different ways. The column is UNIQUE, so on the vanishingly
     * rare derivation collision we draw at random rather than let the insert die
     * on a constraint violation. The existence check drops global scopes on
     * purpose: in demo mode IsDemoRecord hides non-demo rows, and a collision we
     * cannot see is exactly the one that reaches Postgres.
     */
    public static function allocateReference(?string $id = null): string
    {
        $candidate = filled($id) ? self::referenceFromId((string) $id) : self::randomReference();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $taken = static::withoutGlobalScopes()->where('reference', $candidate)->exists();

            if (! $taken) {
                return $candidate;
            }

            $candidate = self::randomReference();
        }

        return $candidate;
    }

    private static function referenceFromId(string $id): string
    {
        $hex = preg_replace('/[^0-9a-f]/i', '', $id);
        $out = '';

        for ($i = 0; $i < 8; $i++) {
            $chunk = hexdec(substr($hex, $i * 4, 4) ?: '0');
            $out .= self::REFERENCE_ALPHABET[$chunk % 32];
        }

        return self::formatReference($out);
    }

    private static function randomReference(): string
    {
        $out = '';

        for ($i = 0; $i < 8; $i++) {
            $out .= self::REFERENCE_ALPHABET[random_int(0, 31)];
        }

        return self::formatReference($out);
    }

    private static function formatReference(string $body): string
    {
        return 'RX-' . substr($body, 0, 4) . '-' . substr($body, 4, 4);
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
