<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['wallet_id', 'payment_id', 'actor_id', 'transaction_type', 'amount', 'reason'];
}
