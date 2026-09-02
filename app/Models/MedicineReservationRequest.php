<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MedicineReservationRequest extends Model
{
    use HasUuids;

    protected $table = 'medicine_reservation_requests';

    protected $fillable = [
        'patient_id',
        'facility_id',
        'medicine_name',
        'quantity_requested',
        'status', // requested, confirmed, rejected, cancelled, expired
        'requested_at',
        'expires_at',
        'confirmed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function facility()
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }
}
