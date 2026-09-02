<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MobileMoneyTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'facility_id', 'provider', 'amount_xaf',
        'phone_number', 'reference', 'description', 'status',
        'provider_ref', 'provider_response', 'completed_at',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'completed_at'      => 'datetime',
    ];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function facility() { return $this->belongsTo(Facility::class); }
}
