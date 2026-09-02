<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MaintenanceWindow extends Model
{
    use HasUuids;

    protected $fillable = [
        'title', 'message', 'starts_at', 'ends_at', 'is_active', 'allow_emergency_access', 'created_by',
    ];

    protected $casts = [
        'starts_at'              => 'datetime',
        'ends_at'                => 'datetime',
        'is_active'              => 'boolean',
        'allow_emergency_access' => 'boolean',
    ];

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) return false;
        $now = now();
        if ($now->lt($this->starts_at)) return false;
        if ($this->ends_at && $now->gt($this->ends_at)) return false;
        return true;
    }
}
