<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['payment_id', 'invoice_id', 'receipt_number', 'amount', 'issued_at'];

    protected $casts = [
        'issued_at' => 'datetime',
    ];
}
