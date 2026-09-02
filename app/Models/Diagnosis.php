<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_id',
        'visit_id',
        'provider_id',
        'code_system',
        'code',
        'snomed_code',
        'snomed_display',
        'display_name',
        'status',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
