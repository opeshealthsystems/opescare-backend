<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MedicationReconciliation extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'provider_id', 'facility_id',
        'medications', 'notes', 'status', 'reviewed_at',
    ];

    protected $casts = [
        'medications' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function provider() { return $this->belongsTo(User::class, 'provider_id'); }
    public function facility() { return $this->belongsTo(Facility::class); }
    public function alerts()   { return $this->hasMany(DrugInteractionAlert::class, 'reconciliation_id'); }
}
