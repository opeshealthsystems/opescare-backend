<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DataRetentionPolicy extends Model
{
    use HasUuids;

    protected $fillable = [
        'table_name', 'retention_days', 'purge_action',
        'legal_basis', 'is_active', 'last_run_at', 'last_run_purged',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'last_run_at' => 'datetime',
    ];
}
