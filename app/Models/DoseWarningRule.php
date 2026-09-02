<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * DoseWarningRule — Module 20 (Clinical Decision Support / Alerts)
 *
 * Defines dosing safety rules for medicines. When a prescription is
 * created, the CDSS checks against active rules and surfaces warnings.
 *
 * CDSS Safety Rule (NON-NEGOTIABLE):
 * Dose warnings ASSIST the prescriber. They do NOT prevent prescribing
 * or replace clinical judgment. The prescriber may override with a reason.
 * "Do not use AI for diagnosis or autonomous clinical decisions."
 */
class DoseWarningRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'medicine_code',
        'medicine_name',
        'warning_type',          // max_single_dose|max_daily_dose|weight_based|age_based|renal_adjusted
        'max_dose_value',
        'dose_unit',
        'patient_population',    // adult|paediatric|elderly|renal_impaired|hepatic_impaired
        'warning_message',
        'severity',              // info|warning|critical
        'requires_override_reason',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'max_dose_value'          => 'float',
        'requires_override_reason' => 'boolean',
        'is_active'               => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForMedicine($query, string $medicineCode)
    {
        return $query->where('medicine_code', $medicineCode);
    }

    public function scopeForPopulation($query, string $population)
    {
        return $query->where(function ($q) use ($population) {
            $q->whereNull('patient_population')
              ->orWhere('patient_population', $population);
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function severityBadgeClass(): string
    {
        return match($this->severity) {
            'critical' => 'badge badge--danger',
            'warning'  => 'badge badge--warning',
            'info'     => 'badge badge--info',
            default    => 'badge badge--neutral',
        };
    }
}
