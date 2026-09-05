<?php

namespace App\Models;

use App\Enums\SelfReportedVitalMeasure;
use App\Enums\VitalProvenance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A vital sign the PATIENT recorded, on their own, at home.
 *
 * See the table migration for why this is not a `triage_vital_signs` row.
 * The short version: no clinical reader queries this table, and the database
 * pins `provenance` to 'self_reported', so a home reading cannot leak into a
 * clinical view by way of a forgotten filter.
 *
 * `provenance` and `source_system` are NOT fillable. They are set by the
 * database defaults and are not a caller's to choose — a write path that could
 * pass `provenance` would be a write path that could lie about it.
 */
class PatientRecordedVital extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'measure',
        'value_primary',
        'value_secondary',
        'unit',
        'measurement_context',
        'device_label',
        'note',
        'measured_at',
        'entered_by_user_id',
    ];

    protected $casts = [
        'measure'         => SelfReportedVitalMeasure::class,
        'value_primary'   => 'float',
        'value_secondary' => 'float',
        'measured_at'     => 'datetime',
        'voided_at'       => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Always self-reported. Read through the enum rather than the raw column so
     * that every consumer gets the same typed answer, and so the one place this
     * could ever change is here.
     */
    public function provenance(): VitalProvenance
    {
        return VitalProvenance::SelfReported;
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    /** Live readings only. A retracted entry is history, not a current value. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }

    public function scopeForPatient(Builder $query, string $patientId): Builder
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Retract a reading. Entries are events: a mistyped value is voided with a
     * reason and re-entered, never edited in place, so the record shows both
     * what was said and that it was taken back.
     */
    public function void(?string $reason = null): bool
    {
        if ($this->isVoided()) {
            return false;
        }

        $this->voided_at = now();
        $this->void_reason = $reason;

        return $this->save();
    }

    /**
     * The value as the patient entered it, at the precision the measure
     * warrants. "120/80" for a blood pressure; "36.8" for a temperature.
     */
    public function displayValue(): string
    {
        $measure = $this->measure;
        $decimals = $measure->decimalsFor((string) $this->unit);

        $primary = number_format((float) $this->value_primary, $decimals, '.', '');

        if (! $measure->hasSecondaryValue() || $this->value_secondary === null) {
            return $primary;
        }

        return $primary . '/' . number_format((float) $this->value_secondary, $decimals, '.', '');
    }
}
