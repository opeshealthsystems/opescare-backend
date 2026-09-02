<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TriageRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'visit_id',
        'nurse_id',
        'presenting_complaint',
        'pain_score',
        'pregnancy_status',
        'acuity_score',
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function nurse()
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function vitalSigns()
    {
        return $this->hasMany(VitalSign::class);
    }
}
