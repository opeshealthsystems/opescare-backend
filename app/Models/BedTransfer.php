<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BedTransfer extends Model
{
    use HasUuids;

    protected $fillable = [
        'admission_id', 'from_bed_id', 'to_bed_id',
        'reason', 'transferred_by', 'transferred_at',
    ];

    protected $casts = ['transferred_at' => 'datetime'];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }
}
