<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'type',
        'status',
        'license_number',
        'parent_organization_id',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'primary_facility_id');
    }
}
