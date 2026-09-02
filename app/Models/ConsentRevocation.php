<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentRevocation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'consent_grant_id',
        'revoked_by',
        'reason',
    ];

    public function consentGrant()
    {
        return $this->belongsTo(ConsentGrant::class);
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
