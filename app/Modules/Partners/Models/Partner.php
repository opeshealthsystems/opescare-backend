<?php

namespace App\Modules\Partners\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function agreements()
    {
        return $this->hasMany(PartnerAgreement::class);
    }

    public function documents()
    {
        return $this->hasMany(PartnerDocument::class);
    }
}
