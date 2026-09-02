<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ClaimSubmission extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'facility_id', 'insurer_name', 'claim_number',
        'service_date', 'billed_amount', 'paid_amount', 'diagnosis_codes',
        'procedure_codes', 'status', 'submitted_at', 'denial_reason',
    ];

    protected $casts = [
        'service_date'    => 'date',
        'submitted_at'    => 'datetime',
        'diagnosis_codes' => 'array',
        'procedure_codes' => 'array',
    ];

    public function patient()    { return $this->belongsTo(Patient::class); }
    public function facility()   { return $this->belongsTo(Facility::class); }
    public function remittances(){ return $this->hasMany(RemittanceAdvice::class); }
}
