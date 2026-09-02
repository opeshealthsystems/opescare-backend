<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, HasUuids;

    // Access logs are immutable; disable updates
    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'actor_id',
        'actor_type',
        'organization_id',
        'facility_id',
        'purpose',
        'data_category',
        'resource_type',
        'resource_id',
        'access_type',
        'emergency_access',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'emergency_access' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
