<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ExportFile extends Model
{
    use HasUuids;

    protected $table = 'public_health_export_files';

    protected $fillable = [
        'report_id',
        'file_type',
        'file_path',
        'file_hash',
        'generated_by',
        'generated_at',
        'download_count',
        'expires_at'
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
        'download_count' => 'integer'
    ];

    public function report()
    {
        return $this->belongsTo(PublicHealthReport::class, 'report_id');
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
