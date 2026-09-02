<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProblemList extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'provider_id', 'icd_code', 'icd_version',
        'description', 'onset_date', 'resolved_date', 'status',
        'priority', 'notes',
        'snomed_code', 'snomed_display',
    ];

    protected $casts = [
        'onset_date'    => 'date',
        'resolved_date' => 'date',
    ];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function provider() { return $this->belongsTo(User::class, 'provider_id'); }

    public function scopeActive($query)   { return $query->where('status', 'active'); }
    public function scopeResolved($query) { return $query->where('status', 'resolved'); }
}
