<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardProfile extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'key',
        'name',
        'portal_prefix',
        'landing_route',
        'description',
    ];

    public function roles()
    {
        return $this->hasMany(Role::class, 'dashboard_profile_key', 'key');
    }

    /** Resolve the full URL for the landing page of this dashboard. */
    public function landingUrl(): string
    {
        try {
            return route($this->landing_route);
        } catch (\Exception) {
            return url('/portals/' . $this->portal_prefix);
        }
    }
}
