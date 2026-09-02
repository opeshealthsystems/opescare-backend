<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentGrant extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'consent_request_id',
        'authorizing_actor',
        'scope',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'scope' => 'array',
        'expires_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function consentRequest()
    {
        return $this->belongsTo(ConsentRequest::class);
    }
}
