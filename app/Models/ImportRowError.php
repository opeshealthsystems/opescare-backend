<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ImportRowError extends Model
{
    use HasUuids;

    protected $fillable = [
        'import_job_id',
        'row_number',
        'field',
        'error_code',
        'message',
        'row_data',
    ];

    protected $casts = [
        'row_data' => 'array',
    ];

    public function importJob()
    {
        return $this->belongsTo(ImportJob::class);
    }
}
