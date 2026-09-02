<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryRecord extends Model
{
    use HasUuids, HasFactory, SoftDeletes;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'pregnancy_record_id', 'patient_id', 'facility_id', 'provider_id',
        'delivery_date', 'delivery_mode', 'indication',
        'duration_labour_hours', 'birth_weight_grams',
        'apgar_1min', 'apgar_5min', 'neonatal_outcome',
        'complications', 'notes',
    ];

    protected $casts = [
        'delivery_date'         => 'date',
        'duration_labour_hours' => 'decimal:2',
        'birth_weight_grams'    => 'integer',
        'apgar_1min'            => 'integer',
        'apgar_5min'            => 'integer',
    ];

    public function pregnancyRecord(): BelongsTo
    {
        return $this->belongsTo(PregnancyRecord::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
