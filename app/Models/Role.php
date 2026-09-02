<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function accountCategory()
    {
        return $this->belongsTo(AccountCategory::class);
    }

    public function dashboardProfile()
    {
        return $this->belongsTo(DashboardProfile::class, 'dashboard_profile_key', 'key');
    }
}
