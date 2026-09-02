<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportAuditEvent extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'import_job_id',
        'action',
        'actor_id',
        'details',
        'occurred_at',
    ];

    protected $casts = [
        'details'     => 'array',
        'occurred_at' => 'datetime',
    ];

    public function importJob()
    {
        return $this->belongsTo(ImportJob::class);
    }
}
