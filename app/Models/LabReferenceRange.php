<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LabReferenceRange extends Model
{
    use HasUuids;

    protected $fillable = [
        'loinc_code', 'test_name', 'unit', 'gender',
        'age_min', 'age_max', 'normal_low', 'normal_high',
        'critical_low', 'critical_high',
    ];

    public function scopeForLoinc($query, string $loinc, string $gender, int $age)
    {
        return $query->where('loinc_code', $loinc)
            ->where(fn($q) => $q->where('gender', $gender)->orWhere('gender', 'all'))
            ->where('age_min', '<=', $age)
            ->where('age_max', '>=', $age);
    }
}
