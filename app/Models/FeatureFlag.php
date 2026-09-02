<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    use HasUuids;

    protected $fillable = [
        'key', 'label', 'description', 'enabled', 'scope', 'scope_value', 'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public static function isEnabled(string $key): bool
    {
        return (bool) static::where('key', $key)->value('enabled');
    }
}
