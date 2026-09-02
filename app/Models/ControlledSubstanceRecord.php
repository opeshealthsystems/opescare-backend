<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ControlledSubstanceRecord extends Model
{
    use HasUuids;

    protected $fillable = [
        'prescription_id', 'patient_id', 'facility_id',
        'prescribed_by', 'dispensed_by', 'drug_name', 'drug_schedule',
        'quantity_dispensed', 'unit', 'dispensed_at', 'batch_number',
        'witness_id', 'notes',
    ];

    protected $casts = ['dispensed_at' => 'datetime'];

    /**
     * Controlled substance records are an immutable audit trail.
     * Corrections require a new record, not an update.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \LogicException('ControlledSubstanceRecord is immutable. Corrections require a new record.');
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \LogicException('ControlledSubstanceRecord is immutable and cannot be saved after creation.');
        }
        return parent::save($options);
    }

    public function prescription() { return $this->belongsTo(Prescription::class); }
    public function patient()      { return $this->belongsTo(Patient::class); }
    public function facility()     { return $this->belongsTo(Facility::class); }
    public function prescriber()   { return $this->belongsTo(User::class, 'prescribed_by'); }
    public function dispenser()    { return $this->belongsTo(User::class, 'dispensed_by'); }
}
