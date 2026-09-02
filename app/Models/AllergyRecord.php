<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllergyRecord extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'provider_id',
        'substance',
        'severity',
        'status',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
