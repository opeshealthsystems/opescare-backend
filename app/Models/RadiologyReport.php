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
        'version','supersedes_report_id','superseded_at',
    ];

    protected $casts = [
        'study_date'     => 'datetime',
        'finalized_at'   => 'datetime',
        'amended_at'     => 'datetime',
        'distributed_at' => 'datetime',
        'distributed_to' => 'array',
        'superseded_at'  => 'datetime',
        'version'        => 'integer',
    ];

    /** Final, amended or corrected: the statuses a patient may be handed. */
    public const RELEASED_STATUSES = ['final', 'amended', 'corrected'];

    public function imagingOrder(): BelongsTo { return $this->belongsTo(ImagingOrder::class, 'imaging_order_id'); }
    public function supersedes(): BelongsTo { return $this->belongsTo(self::class, 'supersedes_report_id'); }

    /** The uploaded PDF (if any) this version carries. */
    public function attachment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MedicalAttachment::class, 'radiology_report_id')
            ->where('status', MedicalAttachment::STATUS_ACTIVE);
    }

    /** The version a reader should see: never a superseded one. */
    public function scopeCurrent(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('superseded_at');
    }

    public function isReleased(): bool
    {
        return in_array($this->status, self::RELEASED_STATUSES, true);
    }

    public function isDistributed(): bool
    {
        return $this->distributed_at !== null;
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function orderedBy(): BelongsTo { return $this->belongsTo(User::class, 'ordered_by'); }
    public function reportedBy(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
}
