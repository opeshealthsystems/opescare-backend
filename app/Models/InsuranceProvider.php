<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceProvider extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'name',
        'code',
        'country_code',
        'logo_url',
        'contact_email',
        'contact_phone',
        'portal_url',
        'api_endpoint',
        'status',
    ];

    public function plans()
    {
        return $this->hasMany(InsurancePlan::class);
    }

    public function activePlans()
    {
        return $this->hasMany(InsurancePlan::class)->where('status', 'active');
    }
}
