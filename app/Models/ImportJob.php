<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportJob extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id',
        'import_type',
        'status',
        'original_filename',
        'stored_path',
        'file_extension',
        'file_size_bytes',
        'detected_headers',
        'mapping',
        'validation_summary',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'duplicate_rows',
        'imported_rows',
        'failed_rows',
        'created_by',
        'approved_by',
        'approved_at',
        'import_started_at',
        'import_completed_at',
        'error_message',
    ];

    protected $casts = [
        'detected_headers'      => 'array',
        'mapping'               => 'array',
        'validation_summary'    => 'array',
        'approved_at'           => 'datetime',
        'import_started_at'     => 'datetime',
        'import_completed_at'   => 'datetime',
    ];

    public function rowErrors()
    {
        return $this->hasMany(ImportRowError::class);
    }

    public function auditEvents()
    {
        return $this->hasMany(ImportAuditEvent::class);
    }

    public function canBeMapped(): bool
    {
        return in_array($this->status, ['uploaded', 'mapping_required']);
    }

    public function canBeValidated(): bool
    {
        return in_array($this->status, ['mapping_required', 'preview_ready', 'validated', 'validation_failed']);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'validated';
    }

    public function canBeImported(): bool
    {
        return $this->status === 'approved_for_import';
    }

    public function canBeRolledBack(): bool
    {
        return in_array($this->status, ['completed', 'completed_with_errors']);
    }

    public function canBeCancelled(): bool
    {
        return !in_array($this->status, ['importing', 'rolled_back', 'cancelled']);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'completed_with_errors', 'failed', 'rolled_back', 'cancelled']);
    }
}
