<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockLocation extends Model
{
    use HasUuids;

    protected $fillable = ['facility_id', 'name', 'code', 'type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function types(): array
    {
        return ['store' => 'Main Store', 'pharmacy' => 'Pharmacy', 'ward' => 'Ward Store',
                'lab' => 'Laboratory', 'theatre' => 'Theatre/OR', 'other' => 'Other'];
    }
}
