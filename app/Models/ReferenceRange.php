<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenceRange extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'test_code',
        'test_name',
        'loinc_code',
        'age_group',
        'sex',
        'unit',
        'normal_low',
        'normal_high',
        'critical_low',
        'critical_high',
        'source',
        'facility_id',
        'is_active',
    ];

    protected $casts = [
        'normal_low'   => 'decimal:4',
        'normal_high'  => 'decimal:4',
        'critical_low' => 'decimal:4',
        'critical_high'=> 'decimal:4',
        'is_active'    => 'boolean',
    ];

    public function facility(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Evaluate a numeric value against this reference range.
     * Returns: 'critical_high' | 'high' | 'normal' | 'low' | 'critical_low'
     */
    public function evaluate(float $value): string
    {
        if ($this->critical_high !== null && $value > (float) $this->critical_high) {
            return 'critical_high';
        }
        if ($this->critical_low !== null && $value < (float) $this->critical_low) {
            return 'critical_low';
        }
        if ($this->normal_high !== null && $value > (float) $this->normal_high) {
            return 'high';
        }
        if ($this->normal_low !== null && $value < (float) $this->normal_low) {
            return 'low';
        }
        return 'normal';
    }

    /**
     * Get HL7/FHIR flag code for a value.
     */
    public function flagCode(float $value): ?string
    {
        return match ($this->evaluate($value)) {
            'critical_high' => 'HH',
            'high'          => 'H',
            'low'           => 'L',
            'critical_low'  => 'LL',
            default         => null,
        };
    }
}
