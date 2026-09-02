<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MortuaryRecord extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'facility_id',
        'patient_id',
        'body_number',
        'full_name',
        'sex',
        'approximate_age',
        'cause_of_death',
        'death_date',
        'admission_date',
        'admitted_by',
        'storage_location',
        'status',
        'identified_by',
        'identified_at',
        'released_at',
        'released_to',
        'released_by',
        'next_of_kin_name',
        'next_of_kin_contact',
        'notes',
    ];

    protected $casts = [
        'death_date'     => 'date',
        'admission_date' => 'date',
        'identified_at'  => 'datetime',
        'released_at'    => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function admittedBy()
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    public function autopsyReports()
    {
        return $this->hasMany(AutopsyReport::class);
    }
}
