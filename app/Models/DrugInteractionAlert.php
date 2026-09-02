<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DrugInteractionAlert extends Model
{
    use HasUuids;

    protected $fillable = [
        'reconciliation_id', 'drug_a', 'drug_b', 'severity',
        'description', 'is_hard_stop', 'acknowledged',
        'acknowledged_by', 'acknowledged_at',
    ];

    protected $casts = [
        'is_hard_stop'    => 'boolean',
        'acknowledged'    => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    public function reconciliation() { return $this->belongsTo(MedicationReconciliation::class); }
    public function acknowledgedBy() { return $this->belongsTo(User::class, 'acknowledged_by'); }
}
