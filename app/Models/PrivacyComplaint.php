<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyComplaint extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'complainant_name', 'complainant_email',
        'complaint_type', 'description', 'status',
        'assigned_to', 'resolution', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'open'          => 'danger',
            'under_review'  => 'warning',
            'resolved'      => 'success',
            'escalated'     => 'danger',
            default         => 'default',
        };
    }
}
