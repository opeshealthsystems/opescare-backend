<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['facility_id', 'service_code', 'description', 'unit_price', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
