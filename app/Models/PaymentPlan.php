<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentPlan extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id', 'facility_id', 'total_amount', 'installment_count',
        'frequency', 'start_date', 'status', 'notes',
    ];

    protected $casts = ['start_date' => 'date'];

    public function patient()      { return $this->belongsTo(Patient::class); }
    public function facility()     { return $this->belongsTo(Facility::class); }
    public function installments() { return $this->hasMany(PaymentPlanItem::class); }
}
