<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdvanceDirective extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'directive_type',
        'is_active',
        'effective_date',
        'expiry_date',
        'document_path',
        'witness_name',
        'witness_date',
        'healthcare_proxy_name',
        'healthcare_proxy_phone',
        'healthcare_proxy_relationship',
        'instructions',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'effective_date' => 'date',
        'expiry_date'    => 'date',
        'witness_date'   => 'date',
        'verified_at'    => 'datetime',
    ];

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function verifier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
