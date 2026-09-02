<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WaitlistEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'provider_id', 'facility_id',
        'preferred_dates', 'reason', 'status', 'notified_at', 'booked_at',
    ];

    protected $casts = [
        'preferred_dates' => 'array',
        'notified_at'     => 'datetime',
        'booked_at'       => 'datetime',
    ];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function provider() { return $this->belongsTo(User::class, 'provider_id'); }
    public function facility() { return $this->belongsTo(Facility::class); }
}
