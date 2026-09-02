<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['facility_id', 'cashier_id', 'status', 'cash_total_amount', 'opened_at', 'closed_at'];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];
}
