<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledSubstanceDispensing extends Model {
    use HasUuids, HasFactory;
    use \App\Traits\HasFacilityScope;

    protected $fillable = [
        'facility_id','patient_id','prescription_id','prescription_item_id',
        'drug_code','drug_name','schedule','quantity_dispensed','unit',
        'dispensed_by','dispensed_at','witness_id','witness_confirmed_at',
        'stock_balance_before','stock_balance_after','lot_number','expiry_date','notes',
    ];

    protected $casts = [
        'quantity_dispensed'   => 'decimal:2',
        'stock_balance_before' => 'decimal:2',
        'stock_balance_after'  => 'decimal:2',
        'dispensed_at'         => 'datetime',
        'witness_confirmed_at' => 'datetime',
        'expiry_date'          => 'date',
    ];

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function dispensedBy(): BelongsTo { return $this->belongsTo(User::class, 'dispensed_by'); }
    public function witness(): BelongsTo { return $this->belongsTo(User::class, 'witness_id'); }
}
