<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One drug line on a prescription.
 *
 * `medicine_id` points at the national catalogue (`medicines`), which is the
 * same identifier the patient-facing medicine finder and the per-pharmacy stock
 * listing use — so a prescribed drug and a stocked drug are the same row, not
 * two strings that happen to look alike. `drug_name` / `drug_code` are still
 * written alongside it as the human-readable snapshot at the time of prescribing.
 *
 * Like its parent, an item is immutable once written: the clinical content is
 * frozen and only the dispensing outcome may be recorded on it.
 */
class PrescriptionItem extends Model
{
    use HasFactory, HasUuids;
    use \App\Traits\HasFacilityScope;

    /**
     * Line statuses that close a line WITHOUT it ever being handed over.
     *
     * A cancelled line is not outstanding — nobody is waiting to collect it —
     * so a script whose only remaining line was cancelled is fully dispensed,
     * not partially. The table documents `pending|dispensed|cancelled`; the two
     * FHIR-shaped siblings are listed as well so a line closed under either
     * vocabulary counts the same way.
     */
    public const CLOSED_STATUSES = ['cancelled', 'voided', 'entered_in_error'];

    /** The clinical order itself — frozen at the moment of prescribing. */
    public const IMMUTABLE_ATTRIBUTES = [
        'prescription_id',
        'medicine_id',
        'drug_name',
        'drug_code',
        'strength',
        'dosage_form',
        'dose',
        'frequency',
        'route',
        'duration_days',
        'quantity',
    ];

    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'drug_name',
        'drug_code',
        'strength',
        'dosage_form',
        'dose',
        'frequency',
        'route',
        'duration_days',
        'quantity',
        'status',
        'dispensed_at',
        'dispense_notes',
    ];

    protected $casts = [
        'dispensed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $item): void {
            foreach (self::IMMUTABLE_ATTRIBUTES as $attribute) {
                if ($item->isDirty($attribute)) {
                    throw new \LogicException(
                        "Prescription item is immutable: '{$attribute}' cannot be changed after it is prescribed. "
                        . 'Amend the prescription instead.'
                    );
                }
            }
        });

        static::deleting(function (self $item): void {
            throw new \LogicException(
                'A prescription item is part of an immutable clinical event and cannot be deleted. '
                . 'Void the prescription instead.'
            );
        });
    }

    public function delete(): bool
    {
        throw new \LogicException(
            'A prescription item is part of an immutable clinical event and cannot be deleted. '
            . 'Void the prescription instead.'
        );
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /** The catalogue entry this line was prescribed against, when one was chosen. */
    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function isDispensed(): bool
    {
        return $this->status === 'dispensed';
    }

    /** Cancelled, voided or entered in error: this line will never be handed over. */
    public function isClosed(): bool
    {
        return in_array((string) $this->status, self::CLOSED_STATUSES, true);
    }

    /** Still owed to the patient — neither handed over nor cancelled. */
    public function isOutstanding(): bool
    {
        return ! $this->isDispensed() && ! $this->isClosed();
    }

    /** Human-readable line, preferring the catalogue name when it is linked. */
    public function displayName(): string
    {
        $name = $this->relationLoaded('medicine') && $this->medicine
            ? $this->medicine->name
            : $this->drug_name;

        return trim($name . ' ' . (string) $this->dose);
    }
}
