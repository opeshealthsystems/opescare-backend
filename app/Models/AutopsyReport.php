<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutopsyReport extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'mortuary_record_id',
        'facility_id',
        'type',
        'pathologist_id',
        'performed_at',
        'gross_findings',
        'microscopic_findings',
        'toxicology_results',
        'cause_of_death_confirmed',
        'manner_of_death',
        'external_findings',
        'notes',
        'status',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function mortuaryRecord()
    {
        return $this->belongsTo(MortuaryRecord::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function pathologist()
    {
        return $this->belongsTo(User::class, 'pathologist_id');
    }
}
