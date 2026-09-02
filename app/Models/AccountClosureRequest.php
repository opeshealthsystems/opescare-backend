<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountClosureRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'reason', 'status',
        'data_delete_requested', 'data_export_requested',
        'reviewed_by', 'review_note', 'reviewed_at', 'completed_at',
    ];

    protected $casts = [
        'data_delete_requested' => 'boolean',
        'data_export_requested' => 'boolean',
        'reviewed_at'           => 'datetime',
        'completed_at'          => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'approved'  => 'info',
            'completed' => 'success',
            'rejected'  => 'danger',
            default     => 'default',
        };
    }
}
