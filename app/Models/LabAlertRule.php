<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LabAlertRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'lab_test_code', 'lab_test_name', 'unit',
        'critical_low', 'normal_low', 'normal_high', 'critical_high',
        'gender_filter', 'age_min', 'age_max', 'is_active',
    ];

    protected $casts = [
        'critical_low'  => 'float',
        'normal_low'    => 'float',
        'normal_high'   => 'float',
        'critical_high' => 'float',
        'is_active'     => 'boolean',
    ];

    /**
     * Evaluate a lab value and return severity level or null if normal.
     */
    public function evaluateValue(float $value): ?string
    {
        if ($this->critical_low !== null && $value < $this->critical_low) {
            return 'critical';
        }
        if ($this->critical_high !== null && $value > $this->critical_high) {
            return 'critical';
        }
        if ($this->normal_low !== null && $value < $this->normal_low) {
            return 'warning';
        }
        if ($this->normal_high !== null && $value > $this->normal_high) {
            return 'warning';
        }
        return null;
    }
}
