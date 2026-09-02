<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SavedFacility extends Model
{
    use HasUuids;

    protected $table = 'saved_facilities';

    protected $fillable = [
        'user_id',
        'facility_id',
        'label',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function facility()
    {
        return $this->belongsTo(CareFacility::class, 'facility_id');
    }
}
