<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'key', 'group', 'value', 'value_type', 'description', 'is_public', 'updated_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function typedValue(): mixed
    {
        return match ($this->value_type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json'    => json_decode($this->value, true),
            default   => $this->value,
        };
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->typedValue() : $default;
    }
}
