<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CriticalValueAlert extends Model
{
    use HasUuids;

    protected $fillable = [
        'lab_result_id', 'patient_id', 'alert_type', 'test_name',
        'result_value', 'critical_threshold', 'acknowledged',
        'acknowledged_by', 'acknowledged_at', 'acknowledgement_note',
    ];

    protected $casts = [
        'acknowledged'    => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    public function labResult()      { return $this->belongsTo(LabResult::class); }
    public function patient()        { return $this->belongsTo(Patient::class); }
    public function acknowledgedBy() { return $this->belongsTo(User::class, 'acknowledged_by'); }
}
