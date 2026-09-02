<?php

namespace App\Modules\Partners\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerAgreement extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
