<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BloodInventory extends Model
{
    use HasUuids;

    protected $table = 'blood_inventories';

    protected $fillable = [
        'facility_id',
        'blood_group',
        'component',
        'available_units',
        'is_expired',
        'is_quarantined',
        'is_unsafe',
        'last_stock_update'
    ];

    protected $casts = [
        'available_units' => 'integer',
        'is_expired' => 'boolean',
        'is_quarantined' => 'boolean',
        'is_unsafe' => 'boolean',
        'last_stock_update' => 'datetime'
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
