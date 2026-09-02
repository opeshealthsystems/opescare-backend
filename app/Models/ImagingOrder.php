<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImagingOrder extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'facility_id',
        'visit_id',
        'ordered_by',
        'modality',
        'body_part',
        'clinical_indication',
        'urgency',
        'status',
        'referring_physician',
        'notes',
        'accession_number',
        'ordered_at',
        'scheduled_at',
        'completed_at',
    ];

    protected $casts = [
        'ordered_at'    => 'datetime',
        'scheduled_at'  => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function visit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function orderedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function report(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RadiologyReport::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isStat(): bool
    {
        return $this->urgency === 'stat';
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'completed'   => 'success',
            'in_progress' => 'info',
            'scheduled'   => 'warning',
            'cancelled'   => 'default',
            default       => 'secondary',
        };
    }

    public function modalityLabel(): string
    {
        return match ($this->modality) {
            'xray'        => 'X-Ray',
            'ct'          => 'CT Scan',
            'mri'         => 'MRI',
            'ultrasound'  => 'Ultrasound',
            'echo'        => 'Echocardiogram',
            'nuclear'     => 'Nuclear Medicine',
            'pet'         => 'PET Scan',
            'fluoroscopy' => 'Fluoroscopy',
            default       => ucfirst($this->modality),
        };
    }
}
