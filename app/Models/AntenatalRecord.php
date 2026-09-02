<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Antenatal care facade over the pregnancy_records table.
 * Exposes the ANC-centric interface (estimated_delivery_date, lmp_date, status)
 * while preserving all existing PregnancyRecord columns and behaviour.
 */
class AntenatalRecord extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'pregnancy_records';

    protected $fillable = [
        'patient_id', 'provider_id', 'facility_id',
        'lmp', 'edd', 'gravida', 'para',
        'pregnancy_status', 'risk_factors', 'high_risk', 'notes',
    ];

    protected $casts = [
        'lmp'            => 'date',
        'edd'            => 'date',
        'high_risk'      => 'boolean',
        'risk_factors'   => 'array',
        'registered_at'  => 'datetime',
    ];

    // Virtual accessors so tests can use the plan-specified interface
    public function getEstimatedDeliveryDateAttribute(): ?\Carbon\Carbon
    {
        return $this->edd;
    }

    public function getLmpDateAttribute(): ?\Carbon\Carbon
    {
        return $this->lmp;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->pregnancy_status;
    }

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function provider() { return $this->belongsTo(User::class, 'provider_id'); }
    public function facility() { return $this->belongsTo(Facility::class); }
    public function visits()   { return $this->hasMany(AntenatalVisit::class, 'pregnancy_record_id'); }
}
