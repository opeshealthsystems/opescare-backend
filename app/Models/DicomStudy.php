<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DicomStudy extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'facility_id', 'lab_order_id', 'study_uid',
        'modality', 'body_part', 'study_date', 'accession_no',
        'pacs_url', 'status', 'description',
    ];

    protected $casts = ['study_date' => 'date'];

    public function patient()  { return $this->belongsTo(Patient::class); }
    public function facility() { return $this->belongsTo(Facility::class); }
    public function labOrder() { return $this->belongsTo(LabOrder::class); }
}
