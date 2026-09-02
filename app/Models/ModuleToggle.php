<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModuleToggle extends Model
{
    use HasUuids;

    protected $fillable = [
        'module_key', 'module_label', 'enabled', 'scope', 'scope_value', 'disable_reason', 'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
