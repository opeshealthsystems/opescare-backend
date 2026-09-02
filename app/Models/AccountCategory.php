<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AccountCategory extends Model
{
    use HasUuids;

    protected $fillable = ['key', 'name', 'sort_order'];

    public function roles()
    {
        return $this->hasMany(Role::class);
    }
}
