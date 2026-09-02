<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabOrder extends Model
{
    use HasFactory, HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'visit_id',
        'ordered_by',
        'test_name',
        'test_code',
        'urgency',
        'status',
        'clinical_indication',
        'notes',
        'ordered_at',
        'collected_at',
        'resulted_at',
    ];

    protected $casts = [
        'ordered_at'   => 'datetime',
        'collected_at' => 'datetime',
        'resulted_at'  => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function results()
    {
        return $this->hasMany(LabResult::class, 'lab_order_id');
    }

    public function isResulted(): bool
    {
        return $this->status === 'resulted';
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'resulted'   => 'success',
            'processing' => 'info',
            'collected'  => 'warning',
            'cancelled'  => 'default',
            default      => 'warning',
        };
    }
}
