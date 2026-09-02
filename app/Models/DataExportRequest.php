<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataExportRequest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'requested_by_user_id',
        'export_type',
        'scope_json',
        'status',
        'approved_by',
        'file_path',
        'expires_at',
    ];

    protected $casts = [
        'scope_json' => 'array',
        'expires_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
