<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasUuids;

    protected $fillable = [
        'facility_id', 'name', 'ward_type', 'total_beds',
        'floor', 'building', 'is_active', 'head_nurse_id', 'notes',
    ];

    protected $casts = ['is_active' => 'boolean', 'total_beds' => 'integer'];

    public function beds()
    {
        return $this->hasMany(Bed::class);
    }

    public function availableBeds()
    {
        return $this->hasMany(Bed::class)->where('status', 'available');
    }

    public function occupancyRate(): float
    {
        $total = $this->total_beds ?: 1;
        $occupied = $this->beds()->where('status', 'occupied')->count();
        return round(($occupied / $total) * 100, 1);
    }

    public static function wardTypes(): array
    {
        return [
            'general'    => 'General',
            'icu'        => 'ICU / Critical Care',
            'maternity'  => 'Maternity',
            'pediatric'  => 'Pediatric',
            'surgical'   => 'Surgical',
            'emergency'  => 'Emergency',
            'isolation'  => 'Isolation',
        ];
    }
}
