<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalNote extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, HasUuids;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'visit_id',
        'provider_id',
        'history_of_present_illness',
        'examination_findings',
        'treatment_plan',
        'status',
        'amends_note_id',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function amendedNote()
    {
        return $this->belongsTo(ClinicalNote::class, 'amends_note_id');
    }
}
