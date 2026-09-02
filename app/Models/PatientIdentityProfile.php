<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientIdentityProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'sex',
        'phone',
        'email',
        'photo_path',
        'preferred_language',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
