<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PharmacyInventory extends Model
{
    use HasUuids;

    protected $table = 'pharmacy_inventories';

    protected $fillable = [
        'facility_id',
        'medicine_name',
        'generic_name',
        'form',
        'strength',
        'stock_status',
        'available_quantity',
        'is_expired',
        'is_recalled',
        'is_quarantined',
        'last_stock_update'
    ];

    protected $casts = [
        'available_quantity' => 'integer',
        'is_expired' => 'boolean',
        'is_recalled' => 'boolean',
        'is_quarantined' => 'boolean',
        'last_stock_update' => 'datetime'
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
