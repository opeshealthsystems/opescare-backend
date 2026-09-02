<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PregnancyRecord extends Model
{
    use HasUuids, HasFactory, SoftDeletes;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_id', 'facility_id', 'provider_id',
        'gravida', 'para', 'edd', 'lmp',
        'pregnancy_status', 'blood_type', 'rhesus_factor',
        'high_risk', 'risk_factors', 'notes', 'registered_at',
    ];

    protected $casts = [
        'gravida'       => 'integer',
        'para'          => 'integer',
        'edd'           => 'date',
        'lmp'           => 'date',
        'high_risk'     => 'boolean',
        'risk_factors'  => 'array',
        'registered_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function antenatalVisits(): HasMany
    {
        return $this->hasMany(AntenatalVisit::class);
    }

    public function deliveryRecords(): HasMany
    {
        return $this->hasMany(DeliveryRecord::class);
    }
}
