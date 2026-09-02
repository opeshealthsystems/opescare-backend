<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'name', 'code', 'contact_person', 'phone', 'email',
        'address', 'tax_id', 'status', 'notes', 'created_by',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
