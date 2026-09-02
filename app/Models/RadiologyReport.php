<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RadiologyReport extends Model {
    use HasUuids, HasFactory, SoftDeletes;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'patient_id','facility_id','imaging_order_id','dicom_study_id','ordered_by','reported_by',
        'modality','body_part','study_date','clinical_indication','findings','impression',
        'recommendation','status','finalized_at','amended_at','amendment_reason',
        'distributed_to','distributed_at',
    ];

    protected $casts = [
        'study_date'     => 'datetime',
        'finalized_at'   => 'datetime',
        'amended_at'     => 'datetime',
        'distributed_at' => 'datetime',
        'distributed_to' => 'array',
    ];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function orderedBy(): BelongsTo { return $this->belongsTo(User::class, 'ordered_by'); }
    public function reportedBy(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
}
