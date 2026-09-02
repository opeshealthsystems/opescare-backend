<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryPolicy extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_code',
        'name',
        'version',
        'effective_from',
        'effective_to',
        'settings_json',
        'status',
    ];

    protected $casts = [
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'settings_json' => 'array',
    ];
}
