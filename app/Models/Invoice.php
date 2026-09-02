<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'billing_account_id',
        'patient_id',
        'facility_id',
        'visit_id',
        'invoice_number',
        'status',
        'subtotal_amount',
        'discount_amount',
        'insurance_covered_amount',
        'patient_responsibility_amount',
        'paid_amount',
        'refunded_amount',
        'balance_amount',
        'issued_at',
        'paid_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
