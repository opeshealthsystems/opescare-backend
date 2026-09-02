<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalIdAccessEvent extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
    
    // Disable updated_at since it's an append-only audit log
    const UPDATED_AT = null;

    protected $fillable = [
        'patient_id',
        'health_id',
        'actor_id',
        'actor_type',
        'facility_id',
        'access_type',
        'purpose',
        'result',
        'ip_address',
        'user_agent',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
