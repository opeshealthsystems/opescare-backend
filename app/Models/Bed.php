<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    use HasUuids;

    protected $fillable = [
        'ward_id', 'bed_number', 'status', 'bed_type',
        'has_oxygen', 'has_monitor',
    ];

    protected $casts = ['has_oxygen' => 'boolean', 'has_monitor' => 'boolean'];

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function activeAdmission()
    {
        return $this->hasOne(Admission::class)->where('status', 'active');
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public static function statuses(): array
    {
        return ['available', 'occupied', 'maintenance', 'reserved'];
    }
}
