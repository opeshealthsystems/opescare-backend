<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One analyte released by a laboratory — a clinical event, and clinical events
 * are immutable here.
 *
 * A released result is never erased. A wrong number is CORRECTED and the
 * correction is documented; a result attached to the wrong patient, the wrong
 * order or the wrong analyte is not "fixed" by re-pointing it, because that
 * silently rewrites what a laboratory said about whom.
 *
 * WHY IN-PLACE CORRECTION IS THE AMENDMENT PATH HERE (and not a new row)
 * ---------------------------------------------------------------------
 * App\Models\Prescription amends by writing a NEW row chained through
 * `amends_prescription_id`. `lab_results` has no such column and no status
 * column, so that shape is not available without a migration. The amendment
 * channel this platform actually built is the in-place correction, and the
 * results inbox is designed around it: MobileResultsInboxController::labSignature()
 * fingerprints `id|value|flag|reference_range|resulted_at` per analyte and
 * re-raises the clinician's acknowledgement the moment that fingerprint moves —
 * "any amendment, a corrected value, a raised flag, a re-issued report". So a
 * bench correcting a value is a supported, notified act; the guard below must
 * not block it.
 *
 * What the guard DOES stop is the two things no amendment ever needs:
 *   1. moving a result onto a different patient / order / analyte, and
 *   2. deleting it.
 *
 * The guards live in the model rather than in a service so that no caller —
 * portal, USSD, governance export, PDF service, mobile labs API, results
 * inbox, console or a future one — can route around them.
 *
 * NOT AFFECTED (deliberately)
 * ---------------------------
 *   - Postgres CASCADE from `patients`. AccountDeletionService::purgeExpiredRetention()
 *     ends a retention period with `$patient->delete()`, and the FK cascade
 *     removes the labs at the database level without ever constructing a model.
 *     That is the ONE sanctioned erasure and it stays legal.
 *   - `updateQuietly()`. DemoLabResultsSeeder re-runs against its own
 *     `demo_seed_key` rows and fires no events on purpose — a seeder must not
 *     trip result-created listeners. Quiet writes stay quiet.
 */
class LabResult extends Model
{
    use HasFactory, HasUuids;
    use \App\Traits\HasFacilityScope;

    /**
     * Whose result this is, which order it came off, and which analyte it
     * measures. Changing any of them re-attributes a released number to a
     * different person, panel or test — the exact silent rewrite the
     * immutability rule exists to stop.
     */
    public const IMMUTABLE_ATTRIBUTES = [
        'lab_order_id',
        'patient_id',
        'parameter_name',
    ];

    /**
     * The amendment surface: everything a laboratory may legitimately correct
     * or re-issue on a result it already released. Kept as an explicit list so
     * amend() cannot be handed an identity column by accident.
     */
    public const AMENDABLE_ATTRIBUTES = [
        'value',
        'unit',
        'reference_range',
        'flag',
        'notes',
        'verified_by',
        'resulted_at',
        'loinc_code',
        'loinc_display',
    ];

    protected $fillable = [
        'lab_order_id',
        'patient_id',
        'parameter_name',
        'value',
        'unit',
        'reference_range',
        'flag',
        'notes',
        'verified_by',
        'resulted_at',
        'loinc_code',
        'loinc_display',
        // Demo provenance markers (add_demo_flags_to_lab_tables).
        'is_demo',
        'demo_seed_key',
    ];

    protected $casts = [
        'is_demo'     => 'boolean',
        'resulted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $result): void {
            foreach (self::IMMUTABLE_ATTRIBUTES as $attribute) {
                if ($result->isDirty($attribute)) {
                    throw new \LogicException(
                        "Lab result is immutable: '{$attribute}' cannot be changed after it is released. "
                        . 'Correct the value, flag or note on this result, or release a new result, instead.'
                    );
                }
            }
        });

        static::deleting(function (self $result): void {
            throw new \LogicException(
                'A lab result is an immutable clinical event and cannot be deleted. '
                . 'Amend it — correct the value and record why in its notes — instead.'
            );
        });
    }

    /**
     * Hard-deleting a clinical event is never legitimate. Overridden as well as
     * hooked so the refusal is identical whether the caller uses delete() or
     * trips the model event.
     */
    public function delete(): bool
    {
        throw new \LogicException(
            'A lab result is an immutable clinical event and cannot be deleted. '
            . 'Amend it — correct the value and record why in its notes — instead.'
        );
    }

    /**
     * The sanctioned correction of an already-released result.
     *
     * Everything an amendment needs and nothing it does not: only the
     * AMENDABLE_ATTRIBUTES may be touched, the reason is mandatory and is
     * appended to the result's own notes (the only place on this table a
     * laboratory can say why a number changed), and `resulted_at` is restamped
     * so the record shows when the corrected value was released.
     *
     * Saving normally is still allowed — this is the documented door, not the
     * only one — but a caller that uses it cannot forget the rationale.
     *
     * @param  array<string, mixed>  $changes
     */
    public function amend(array $changes, string $reason, ?string $verifiedBy = null): self
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new \InvalidArgumentException(
                'Amending a released lab result requires a documented reason.'
            );
        }

        $illegal = array_diff(array_keys($changes), self::AMENDABLE_ATTRIBUTES);

        if ($illegal !== []) {
            throw new \LogicException(
                'Lab result is immutable: ' . implode(', ', $illegal)
                . ' cannot be amended. Only ' . implode(', ', self::AMENDABLE_ATTRIBUTES) . ' may be corrected.'
            );
        }

        $stamp = now();

        $this->fill($changes);
        $this->notes = trim(implode("\n", array_filter([
            $this->notes,
            'Amended ' . $stamp->toDateTimeString() . ': ' . $reason,
        ])));
        $this->resulted_at = $changes['resulted_at'] ?? $stamp;

        if ($verifiedBy !== null) {
            $this->verified_by = $verifiedBy;
        }

        $this->save();

        return $this;
    }

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }

    /** The laboratory member who entered or verified this analyte. */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * The flag in the lab desk's vocabulary — H | L | N | C — or null when the
     * laboratory recorded none. Every critical spelling this platform knows
     * (HH, LL, CRITICAL, PANIC…) reads as C; an unknown code is returned as
     * recorded rather than hidden, because it could be a critical one from a
     * dictionary this codebase has not met.
     */
    public function deskFlag(): ?string
    {
        $flag = strtoupper(trim((string) $this->flag));

        if ($flag === '') {
            return null;
        }

        return match ($flag) {
            'HH', 'LL', 'AA', 'C', 'CRIT', 'CRITICAL', 'PANIC' => 'C',
            'H', 'HIGH'                                       => 'H',
            'L', 'LOW'                                        => 'L',
            'N', 'NORM', 'NORMAL', 'WNL'                      => 'N',
            default                                           => $flag,
        };
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isAbnormal(): bool
    {
        return in_array($this->flag, ['H', 'L', 'HH', 'LL', 'abnormal'], true);
    }

    public function flagLabel(): string
    {
        return match ($this->flag) {
            'H'        => 'High',
            'HH'       => 'Critical High',
            'L'        => 'Low',
            'LL'       => 'Critical Low',
            'abnormal' => 'Abnormal',
            default    => 'Normal',
        };
    }
}
