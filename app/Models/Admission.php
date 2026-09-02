<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'patient_id', 'bed_id', 'visit_id',
        'admitted_by', 'attending_physician_id',
        'status', 'admission_reason', 'discharge_reason',
        'discharge_destination', 'admitted_at', 'discharged_at',
    ];

    protected $casts = [
        'admitted_at'    => 'datetime',
        'discharged_at'  => 'datetime',
    ];

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function transfers()
    {
        return $this->hasMany(BedTransfer::class);
    }

    public function lengthOfStay(): ?int
    {
        $end = $this->discharged_at ?? now();
        return (int) $this->admitted_at->diffInDays($end);
    }
}
